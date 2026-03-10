<?php

namespace App\Http\Controllers;

use App\Services\CustomerAuthService;
use App\Services\FrontendCatalogService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerAuthController extends Controller
{
    protected FrontendCatalogService $frontendCatalogService;
    protected CustomerAuthService $customerAuthService;

    public function __construct(FrontendCatalogService $frontendCatalogService, CustomerAuthService $customerAuthService)
    {
        $this->frontendCatalogService = $frontendCatalogService;
        $this->customerAuthService = $customerAuthService;
    }

    public function register()
    {
        $menuCategories = $this->frontendCatalogService->getMenuCategories();
        return view('frontend.register', compact('menuCategories'));
    }

    public function login()
    {
        $menuCategories = $this->frontendCatalogService->getMenuCategories();
        return view('frontend.login', compact('menuCategories'));
    }

    public function storeLogin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mobile_number' => ['required', 'regex:/^[0-9]{10,15}$/'],
        ]);

        $mobileNumber = $this->customerAuthService->normalizeMobile($validated['mobile_number']);
        $customer = $this->customerAuthService->findCustomerByMobile($mobileNumber);

        if (!$customer) {
            return redirect()
                ->route('frontend.login')
                ->withInput()
                ->withErrors(['mobile_number' => 'Mobile number not registered. Please sign up first.']);
        }

        if ((int) $customer->is_active !== 1) {
            return redirect()
                ->route('frontend.login')
                ->withInput()
                ->withErrors(['mobile_number' => 'This account is inactive. Please contact support.']);
        }

        try {
            $this->customerAuthService->createLoginOtpForCustomer($customer);
        } catch (\RuntimeException $exception) {
            return redirect()
                ->route('frontend.login')
                ->withInput()
                ->withErrors(['mobile_number' => $exception->getMessage()]);
        } catch (\Throwable $exception) {
            return redirect()
                ->route('frontend.login')
                ->withInput()
                ->withErrors(['mobile_number' => 'Unable to send OTP now. Please try again.']);
        }

        return redirect()
            ->route('frontend.login.otp');
    }

    public function storeRegister(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'mobile_number' => ['required', 'regex:/^[0-9]{10,15}$/'],
        ]);

        $mobileNumber = $this->customerAuthService->normalizeMobile($validated['mobile_number']);
        $existingCustomer = $this->customerAuthService->findCustomerByMobile($mobileNumber);

        if ($existingCustomer) {
            return redirect()
                ->route('frontend.login')
                ->withInput(['mobile_number' => $mobileNumber])
                ->with('error', 'This mobile number ' . $mobileNumber . ' is already registered. Please login with mobile number.');
        }

        try {
            $this->customerAuthService->createCustomerAndSendOtp($validated['name'], $mobileNumber);
        } catch (\RuntimeException $exception) {
            return redirect()
                ->route('frontend.register')
                ->withInput()
                ->withErrors(['mobile_number' => $exception->getMessage()]);
        } catch (\Throwable $exception) {
            return redirect()
                ->route('frontend.register')
                ->withInput()
                ->withErrors(['mobile_number' => 'Unable to complete registration now. Please try again.']);
        }

        return redirect()->route('frontend.register.otp');
    }

    public function showRegisterOtp()
    {
        $otpRecord = $this->customerAuthService->getActiveOtpFromSession();
        if (!$otpRecord) {
            return redirect()
                ->route('frontend.register')
                ->withErrors(['otp' => 'Please register first to receive OTP.']);
        }

        if (Carbon::now()->greaterThan($otpRecord->otp_expires_at)) {
            session()->forget('register_otp_id');

            return redirect()
                ->route('frontend.register')
                ->withErrors(['otp' => 'OTP expired. Please register again.']);
        }

        $menuCategories = $this->frontendCatalogService->getMenuCategories();
        $countdownDeadline = $otpRecord->otp_expires_at->timestamp;

        return view('frontend.register_otp', [
            'menuCategories' => $menuCategories,
            'mobileNumber' => (string) ($otpRecord->customer?->mobile_number ?? ''),
            'countdownDeadline' => $countdownDeadline,
            'isVerified' => false,
        ]);
    }

    public function verifyRegisterOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'otp_code' => ['required', 'digits:6'],
        ]);

        $result = $this->customerAuthService->verifyOtpAndLogin($validated['otp_code']);

        if (($result['status'] ?? '') === 'session_expired') {
            return redirect()
                ->route('frontend.register')
                ->withErrors(['otp' => 'Session expired. Please register again.']);
        }

        if (($result['status'] ?? '') === 'expired') {
            return redirect()
                ->route('frontend.register')
                ->withErrors(['otp' => 'OTP expired. Please register again.']);
        }

        if (($result['status'] ?? '') === 'invalid') {
            return redirect()
                ->route('frontend.register.otp')
                ->withErrors(['otp_code' => 'Invalid OTP. Please try again.']);
        }

        if (($result['status'] ?? '') !== 'verified') {
            return redirect()
                ->route('frontend.register')
                ->withErrors(['otp' => 'Unable to verify OTP. Please register again.']);
        }

        return redirect()
            ->route('frontend.home')
            ->with('success', 'Mobile number verified and logged in successfully.');
    }

    public function showLoginOtp()
    {
        $otpRecord = $this->customerAuthService->getActiveLoginOtpFromSession();
        if (!$otpRecord) {
            return redirect()
                ->route('frontend.login')
                ->withErrors(['otp' => 'Please login first to receive OTP.']);
        }

        if (Carbon::now()->greaterThan($otpRecord->otp_expires_at)) {
            session()->forget('login_otp_id');

            return redirect()
                ->route('frontend.login')
                ->withErrors(['otp' => 'OTP expired. Please login again.']);
        }

        $menuCategories = $this->frontendCatalogService->getMenuCategories();
        $countdownDeadline = $otpRecord->otp_expires_at->timestamp;

        return view('frontend.login_otp', [
            'menuCategories' => $menuCategories,
            'mobileNumber' => (string) ($otpRecord->customer?->mobile_number ?? ''),
            'countdownDeadline' => $countdownDeadline,
        ]);
    }

    public function verifyLoginOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'otp_code' => ['required', 'digits:6'],
        ]);

        $result = $this->customerAuthService->verifyOtpAndLoginBySessionKey($validated['otp_code'], 'login_otp_id');

        if (($result['status'] ?? '') === 'session_expired') {
            return redirect()
                ->route('frontend.login')
                ->withErrors(['otp' => 'Session expired. Please login again.']);
        }

        if (($result['status'] ?? '') === 'expired') {
            return redirect()
                ->route('frontend.login')
                ->withErrors(['otp' => 'OTP expired. Please login again.']);
        }

        if (($result['status'] ?? '') === 'invalid') {
            return redirect()
                ->route('frontend.login.otp')
                ->withErrors(['otp_code' => 'Invalid OTP. Please try again.']);
        }

        if (($result['status'] ?? '') !== 'verified') {
            return redirect()
                ->route('frontend.login')
                ->withErrors(['otp' => 'Unable to verify OTP. Please login again.']);
        }

        return redirect()
            ->route('frontend.home')
            ->with('success', 'Logged in successfully.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->customerAuthService->logoutCustomer($request);

        return redirect()->route('frontend.home')->with('success', 'Logged out successfully.');
    }
}
