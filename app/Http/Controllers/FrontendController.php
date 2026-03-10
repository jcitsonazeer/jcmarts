<?php

namespace App\Http\Controllers;

use App\Models\CustomerRegisterOtp;
use App\Services\FrontendCatalogService;
use App\Services\OtpSmsService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FrontendController extends Controller
{
    protected $frontendCatalogService;
    protected $otpSmsService;

    public function __construct(FrontendCatalogService $frontendCatalogService, OtpSmsService $otpSmsService)
    {
        $this->frontendCatalogService = $frontendCatalogService;
        $this->otpSmsService = $otpSmsService;
    }

    public function index()
    {
        $menuCategories = $this->frontendCatalogService->getMenuCategories();
        $indexBanners = $this->frontendCatalogService->getIndexBanners();
        $topSubCategories = $this->frontendCatalogService->getTopSubCategories();
        $productOffers = $this->frontendCatalogService->getProductOffers();
        $featuredProducts = $this->frontendCatalogService->getFeaturedProducts();

        return view('frontend.index', compact('menuCategories', 'indexBanners', 'topSubCategories', 'productOffers', 'featuredProducts'));
    }

    public function products()
    {
        $menuCategories = $this->frontendCatalogService->getMenuCategories();

        return view('frontend.products', compact('menuCategories'));
    }

    public function single_product()
    {
        $menuCategories = $this->frontendCatalogService->getMenuCategories();

        return view('frontend.single_product', compact('menuCategories'));
    }

    public function cart()
    {
        $menuCategories = $this->frontendCatalogService->getMenuCategories();

        return view('frontend.cart', compact('menuCategories'));
    }

    public function checkout()
    {
        $menuCategories = $this->frontendCatalogService->getMenuCategories();

        return view('frontend.checkout', compact('menuCategories'));
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

    public function storeRegister(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'mobile_number' => ['required', 'regex:/^[0-9]{10,15}$/'],
        ]);

        $existingMobile = CustomerRegisterOtp::query()
            ->where('mobile_number', $validated['mobile_number'])
            ->orderBy('id')
            ->first();

        if ($existingMobile) {
            return redirect()
                ->route('frontend.register')
                ->withInput()
                ->with('mobile_exists_name', $existingMobile->customer_name)
                ->with('mobile_exists_number', $validated['mobile_number']);
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $now = Carbon::now();

        $otpEntry = CustomerRegisterOtp::create([
            'customer_name' => $validated['name'],
            'mobile_number' => $validated['mobile_number'],
            'otp_code' => $otp,
            'otp_expires_at' => $now->copy()->addMinutes(3),
            'is_active' => 1,
            'created_by_id' => null,
            'created_date' => $now,
        ]);

        $smsResponse = $this->otpSmsService->sendOtp($validated['mobile_number'], $otp, $validated['name']);
        if (!($smsResponse['success'] ?? false)) {
            $otpEntry->delete();

            return redirect()
                ->route('frontend.register')
                ->withInput()
                ->withErrors(['mobile_number' => 'Failed to send OTP SMS. Please try again.']);
        }

        session([
            'register_otp_id' => $otpEntry->id,
            'register_mobile_number' => $validated['mobile_number'],
        ]);

        return redirect()
            ->route('frontend.register.otp');
    }

    public function showRegisterOtp()
    {
        if (session()->has('verified_success')) {
            return view('frontend.register_otp', [
                'mobileNumber' => (string) session('verified_mobile', ''),
                'countdownDeadline' => Carbon::now()->timestamp,
                'isVerified' => true,
            ]);
        }

        $otpId = session('register_otp_id');
        if (!$otpId) {
            return redirect()
                ->route('frontend.register')
                ->withErrors(['otp' => 'Please register first to receive OTP.']);
        }

        $otpRecord = CustomerRegisterOtp::query()
            ->where('id', $otpId)
            ->where('is_active', 1)
            ->first();

        if (!$otpRecord || Carbon::now()->greaterThan($otpRecord->otp_expires_at)) {
            session()->forget(['register_otp_id', 'register_mobile_number']);
            return redirect()
                ->route('frontend.register')
                ->withErrors(['otp' => 'OTP expired. Please register again.']);
        }

        $createdTimestamp = $otpRecord->created_date
            ? $otpRecord->created_date->copy()->addSeconds(150)->timestamp
            : Carbon::now()->addSeconds(150)->timestamp;

        $countdownDeadline = min($createdTimestamp, $otpRecord->otp_expires_at->timestamp);

        return view('frontend.register_otp', [
            'mobileNumber' => (string) session('register_mobile_number', $otpRecord->mobile_number),
            'countdownDeadline' => $countdownDeadline,
            'isVerified' => false,
        ]);
    }

    public function verifyRegisterOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'otp_code' => ['required', 'digits:6'],
        ]);

        $otpId = session('register_otp_id');
        if (!$otpId) {
            return redirect()
                ->route('frontend.register')
                ->withErrors(['otp' => 'Session expired. Please register again.']);
        }

        $otpRecord = CustomerRegisterOtp::query()
            ->where('id', $otpId)
            ->where('is_active', 1)
            ->first();

        if (!$otpRecord || Carbon::now()->greaterThan($otpRecord->otp_expires_at)) {
            session()->forget(['register_otp_id', 'register_mobile_number']);
            return redirect()
                ->route('frontend.register')
                ->withErrors(['otp' => 'OTP expired. Please register again.']);
        }

        if (!hash_equals((string) $otpRecord->otp_code, (string) $validated['otp_code'])) {
            return redirect()
                ->route('frontend.register.otp')
                ->withErrors(['otp_code' => 'Invalid OTP. Please try again.']);
        }

        $otpRecord->is_active = 0;
        $otpRecord->updated_date = Carbon::now();
        $otpRecord->save();

        session()->forget(['register_otp_id', 'register_mobile_number']);

        return redirect()
            ->route('frontend.register.otp')
            ->with('verified_success', 'user VERIFIED')
            ->with('verified_mobile', $otpRecord->mobile_number);
    }

    public function storeLogin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mobile_number' => ['required', 'regex:/^[0-9]{10,15}$/'],
        ]);

        $registeredUser = CustomerRegisterOtp::query()
            ->where('mobile_number', $validated['mobile_number'])
            ->orderBy('id')
            ->first();

        if (!$registeredUser) {
            return redirect()
                ->route('frontend.login')
                ->withInput()
                ->withErrors(['mobile_number' => 'Mobile number not registered. Please sign up first.']);
        }

        return redirect()
            ->route('frontend.login')
            ->with('success', 'Login request accepted for ' . $registeredUser->customer_name . '.');
    }
	
	public function addAddress()
{
    return view('frontend.add_address');
}
}
