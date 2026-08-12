<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerRegisterOtp;
use App\Services\CustomerAuthService;
use App\Services\OtpSmsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        protected CustomerAuthService $customerAuthService,
        protected OtpSmsService $otpSmsService
    ) {
    }

    public function sendOtp(Request $request)
    {
        $validated = $request->validate([
            'mobile_number' => ['required_without:mobile', 'nullable', 'regex:/^[0-9]{10,15}$/'],
            'mobile' => ['required_without:mobile_number', 'nullable', 'regex:/^[0-9]{10,15}$/'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        $mobileNumber = $this->customerAuthService->normalizeMobile(
            (string) ($validated['mobile_number'] ?? $validated['mobile'] ?? '')
        );

        $now = Carbon::now();
        $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $result = DB::transaction(function () use ($validated, $mobileNumber, $otpCode, $now) {
            $customer = Customer::query()
                ->where('mobile_number', $mobileNumber)
                ->first();

            if (!$customer) {
                $customer = Customer::create([
                    'name' => trim((string) ($validated['name'] ?? 'Customer')),
                    'mobile_number' => $mobileNumber,
                    'verified_status' => 'pending',
                    'is_active' => 1,
                    'created_date' => $now,
                ]);
            }

            if (!$customer->is_active) {
                throw ValidationException::withMessages([
                    'mobile_number' => ['This account is inactive. Please contact support.'],
                ]);
            }

            CustomerRegisterOtp::query()
                ->where('customer_id', $customer->id)
                ->where('is_active', 1)
                ->update([
                    'is_active' => 0,
                    'updated_date' => $now,
                ]);

            $otp = CustomerRegisterOtp::create([
                'customer_id' => $customer->id,
                'otp_code' => $otpCode,
                'otp_expires_at' => $now->copy()->addMinutes(3),
                'is_active' => 1,
                'created_date' => $now,
            ]);

            return ['customer' => $customer, 'otp' => $otp];
        });

        $smsResponse = app()->environment(['local', 'testing']) && blank(config('services.sms_api.key'))
            ? ['success' => true, 'message' => 'OTP generated for local testing.']
            : $this->otpSmsService->sendOtp(
                $result['customer']->mobile_number,
                $otpCode,
                $result['customer']->name
            );

        if (!($smsResponse['success'] ?? false)) {
            return response()->json([
                'status' => false,
                'message' => $smsResponse['message'] ?? 'Failed to send OTP SMS. Please try again.',
                'data' => null,
            ], 500);
        }

        $data = [
            'mobile_number' => $result['customer']->mobile_number,
            'expires_in_seconds' => 180,
        ];

        if (!app()->environment('production')) {
            $data['otp'] = $otpCode;
        }

        return response()->json([
            'status' => true,
            'message' => 'OTP sent successfully',
            'data' => $data,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'mobile_number' => ['required_without:mobile', 'nullable', 'regex:/^[0-9]{10,15}$/'],
            'mobile' => ['required_without:mobile_number', 'nullable', 'regex:/^[0-9]{10,15}$/'],
            'otp' => ['required', 'digits:6'],
        ]);

        $mobileNumber = $this->customerAuthService->normalizeMobile(
            (string) ($validated['mobile_number'] ?? $validated['mobile'] ?? '')
        );

        $otp = CustomerRegisterOtp::query()
            ->with('customer')
            ->whereHas('customer', function ($query) use ($mobileNumber) {
                $query->where('mobile_number', $mobileNumber)
                    ->where('is_active', 1);
            })
            ->where('otp_code', $validated['otp'])
            ->where('is_active', 1)
            ->latest('id')
            ->first();

        if (!$otp || !$otp->customer) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP',
                'data' => null,
            ], 401);
        }

        if (Carbon::now()->greaterThan($otp->otp_expires_at)) {
            $otp->update([
                'is_active' => 0,
                'updated_date' => Carbon::now(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'OTP expired',
                'data' => null,
            ], 401);
        }

        $customer = $otp->customer;
        $now = Carbon::now();

        DB::transaction(function () use ($otp, $customer, $now) {
            $otp->update([
                'is_active' => 0,
                'updated_date' => $now,
            ]);

            $customer->update([
                'verified_status' => 'verified',
                'updated_date' => $now,
            ]);
        });

        $token = $customer->createToken('flutter-app')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'mobile_number' => $customer->mobile_number,
                    'verified_status' => $customer->verified_status,
                ],
            ],
        ]);
    }

    public function sendRegisterOtp(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'mobile_number' => ['required_without:mobile', 'nullable', 'regex:/^[0-9]{10,15}$/'],
            'mobile' => ['required_without:mobile_number', 'nullable', 'regex:/^[0-9]{10,15}$/'],
        ]);

        $mobileNumber = $this->customerAuthService->normalizeMobile(
            (string) ($validated['mobile_number'] ?? $validated['mobile'] ?? '')
        );

        $now = Carbon::now();
        $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $result = DB::transaction(function () use ($validated, $mobileNumber, $otpCode, $now) {
            $customer = Customer::query()
                ->where('mobile_number', $mobileNumber)
                ->first();

            if ($customer && !$customer->is_active) {
                throw ValidationException::withMessages([
                    'mobile_number' => ['This account is inactive. Please contact support.'],
                ]);
            }

            if ($customer && $customer->verified_status === 'verified') {
                throw ValidationException::withMessages([
                    'mobile_number' => ['This mobile number is already registered. Please login.'],
                ]);
            }

            if (!$customer) {
                $customer = Customer::create([
                    'name' => trim($validated['name']),
                    'mobile_number' => $mobileNumber,
                    'verified_status' => 'pending',
                    'is_active' => 1,
                    'created_date' => $now,
                ]);
            } else {
                $customer->update([
                    'name' => trim($validated['name']),
                    'updated_date' => $now,
                ]);
            }

            CustomerRegisterOtp::query()
                ->where('customer_id', $customer->id)
                ->where('is_active', 1)
                ->update([
                    'is_active' => 0,
                    'updated_date' => $now,
                ]);

            $otp = CustomerRegisterOtp::create([
                'customer_id' => $customer->id,
                'otp_code' => $otpCode,
                'otp_expires_at' => $now->copy()->addMinutes(3),
                'is_active' => 1,
                'created_date' => $now,
            ]);

            return ['customer' => $customer, 'otp' => $otp];
        });

        $smsResponse = app()->environment(['local', 'testing']) && blank(config('services.sms_api.key'))
            ? ['success' => true, 'message' => 'OTP generated for local testing.']
            : $this->otpSmsService->sendOtp(
                $result['customer']->mobile_number,
                $otpCode,
                $result['customer']->name
            );

        if (!($smsResponse['success'] ?? false)) {
            return response()->json([
                'status' => false,
                'message' => $smsResponse['message'] ?? 'Failed to send OTP SMS. Please try again.',
                'data' => null,
            ], 500);
        }

        $data = [
            'mobile_number' => $result['customer']->mobile_number,
            'expires_in_seconds' => 180,
        ];

        if (!app()->environment('production')) {
            $data['otp'] = $otpCode;
        }

        return response()->json([
            'status' => true,
            'message' => 'Registration OTP sent successfully',
            'data' => $data,
        ]);
    }

    public function verifyRegisterOtp(Request $request)
    {
        $validated = $request->validate([
            'mobile_number' => ['required_without:mobile', 'nullable', 'regex:/^[0-9]{10,15}$/'],
            'mobile' => ['required_without:mobile_number', 'nullable', 'regex:/^[0-9]{10,15}$/'],
            'otp' => ['required', 'digits:6'],
        ]);

        $mobileNumber = $this->customerAuthService->normalizeMobile(
            (string) ($validated['mobile_number'] ?? $validated['mobile'] ?? '')
        );

        $otp = CustomerRegisterOtp::query()
            ->with('customer')
            ->whereHas('customer', function ($query) use ($mobileNumber) {
                $query->where('mobile_number', $mobileNumber)
                    ->where('is_active', 1)
                    ->where('verified_status', 'pending');
            })
            ->where('otp_code', $validated['otp'])
            ->where('is_active', 1)
            ->latest('id')
            ->first();

        if (!$otp || !$otp->customer) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP',
                'data' => null,
            ], 401);
        }

        if (Carbon::now()->greaterThan($otp->otp_expires_at)) {
            $otp->update([
                'is_active' => 0,
                'updated_date' => Carbon::now(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'OTP expired',
                'data' => null,
            ], 401);
        }

        $customer = $otp->customer;
        $now = Carbon::now();

        DB::transaction(function () use ($otp, $customer, $now) {
            $otp->update([
                'is_active' => 0,
                'updated_date' => $now,
            ]);

            $customer->update([
                'verified_status' => 'verified',
                'updated_date' => $now,
            ]);
        });

        $token = $customer->createToken('flutter-app')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Registration completed successfully',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'mobile_number' => $customer->mobile_number,
                    'verified_status' => $customer->verified_status,
                ],
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'status' => true,
            'message' => 'Logged out successfully',
            'data' => null,
        ]);
    }
}
