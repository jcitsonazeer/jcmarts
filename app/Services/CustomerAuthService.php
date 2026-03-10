<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerRegisterOtp;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerAuthService
{
    protected OtpSmsService $otpSmsService;

    public function __construct(OtpSmsService $otpSmsService)
    {
        $this->otpSmsService = $otpSmsService;
    }

    public function normalizeMobile(string $mobileNumber): string
    {
        return preg_replace('/\D+/', '', $mobileNumber) ?? '';
    }

    public function findCustomerByMobile(string $mobileNumber): ?Customer
    {
        return Customer::query()
            ->where('mobile_number', $mobileNumber)
            ->where('is_active', 1)
            ->first();
    }

    public function createCustomerAndSendOtp(string $name, string $mobileNumber): array
    {
        $now = Carbon::now();
        $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        return DB::transaction(function () use ($name, $mobileNumber, $otpCode, $now) {
            $customer = Customer::create([
                'name' => $name,
                'mobile_number' => $mobileNumber,
                'verified_status' => 'pending',
                'is_active' => 1,
                'created_date' => $now,
            ]);

            $otpEntry = CustomerRegisterOtp::create([
                'customer_id' => $customer->id,
                'otp_code' => $otpCode,
                'otp_expires_at' => $now->copy()->addMinutes(3),
                'is_active' => 1,
                'created_date' => $now,
            ]);

            $smsResponse = $this->otpSmsService->sendOtp($mobileNumber, $otpCode, $name);
            if (!($smsResponse['success'] ?? false)) {
                throw new \RuntimeException('Failed to send OTP SMS. Please try again.');
            }

            session([
                'register_otp_id' => $otpEntry->id,
            ]);

            return [
                'customer' => $customer,
                'otp' => $otpEntry,
            ];
        });
    }

    public function createLoginOtpForCustomer(Customer $customer): array
    {
        $now = Carbon::now();
        $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        return DB::transaction(function () use ($customer, $otpCode, $now) {
            CustomerRegisterOtp::query()
                ->where('customer_id', $customer->id)
                ->where('is_active', 1)
                ->update([
                    'is_active' => 0,
                    'updated_date' => $now,
                ]);

            $otpEntry = CustomerRegisterOtp::create([
                'customer_id' => $customer->id,
                'otp_code' => $otpCode,
                'otp_expires_at' => $now->copy()->addMinutes(3),
                'is_active' => 1,
                'created_date' => $now,
            ]);

            $smsResponse = $this->otpSmsService->sendOtp($customer->mobile_number, $otpCode, $customer->name);
            if (!($smsResponse['success'] ?? false)) {
                throw new \RuntimeException('Failed to send OTP SMS. Please try again.');
            }

            session(['login_otp_id' => $otpEntry->id]);

            return [
                'customer' => $customer,
                'otp' => $otpEntry,
            ];
        });
    }

    public function getActiveOtpFromSession(): ?CustomerRegisterOtp
    {
        return $this->getActiveOtpFromSessionKey('register_otp_id');
    }

    public function getActiveLoginOtpFromSession(): ?CustomerRegisterOtp
    {
        return $this->getActiveOtpFromSessionKey('login_otp_id');
    }

    public function getActiveOtpFromSessionKey(string $sessionKey): ?CustomerRegisterOtp
    {
        $otpId = session($sessionKey);
        if (!$otpId) {
            return null;
        }

        return CustomerRegisterOtp::query()
            ->with('customer')
            ->where('id', $otpId)
            ->where('is_active', 1)
            ->first();
    }

    public function verifyOtpAndLogin(string $otpCode): array
    {
        return $this->verifyOtpAndLoginBySessionKey($otpCode, 'register_otp_id');
    }

    public function verifyOtpAndLoginBySessionKey(string $otpCode, string $sessionKey): array
    {
        $otpRecord = $this->getActiveOtpFromSessionKey($sessionKey);
        if (!$otpRecord) {
            return ['status' => 'session_expired'];
        }

        if (Carbon::now()->greaterThan($otpRecord->otp_expires_at)) {
            session()->forget($sessionKey);
            return ['status' => 'expired'];
        }

        if (!hash_equals((string) $otpRecord->otp_code, (string) $otpCode)) {
            return ['status' => 'invalid'];
        }

        $customer = $otpRecord->customer;
        if (!$customer) {
            session()->forget($sessionKey);
            return ['status' => 'customer_missing'];
        }

        $now = Carbon::now();

        DB::transaction(function () use ($otpRecord, $customer, $now) {
            $otpRecord->is_active = 0;
            $otpRecord->updated_date = $now;
            $otpRecord->save();

            $customer->verified_status = 'verified';
            $customer->updated_date = $now;
            $customer->save();
        });

        session()->forget($sessionKey);
        $this->loginCustomer($customer);

        return ['status' => 'verified', 'customer' => $customer];
    }

    public function loginCustomer(Customer $customer): void
    {
        session([
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
        ]);
    }

    public function logoutCustomer(Request $request): void
    {
        session()->forget(['customer_id', 'customer_name', 'register_otp_id', 'login_otp_id']);
        $request->session()->regenerateToken();
    }

    public function isCustomerLoggedIn(): bool
    {
        return !empty(session('customer_id'));
    }
}
