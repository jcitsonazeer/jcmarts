<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPerson;
use App\Models\DeliveryPersonOtp;
use App\Services\OtpSmsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryAuthController extends Controller
{
    public function __construct(
        protected OtpSmsService $otpSmsService
    ) {
    }

    /**
     * POST /api/v1/delivery/otp/send
     *
     * Send an OTP to a registered delivery person's mobile number.
     * OTP is only sent if the mobile number exists and the account is active.
     */
    public function sendOtp(Request $request)
    {
        $validated = $request->validate([
            'mobile' => ['required', 'regex:/^[0-9]{10,15}$/'],
        ]);

        $mobileNumber = preg_replace('/\D+/', '', $validated['mobile']) ?? '';

        $deliveryPerson = DeliveryPerson::query()
            ->where('mobile', $mobileNumber)
            ->first();

        if (!$deliveryPerson) {
            return response()->json([
                'status' => false,
                'message' => 'Mobile number not registered as a delivery person.',
                'data' => null,
            ], 404);
        }

        if ($deliveryPerson->status !== 'active') {
            return response()->json([
                'status' => false,
                'message' => 'This delivery person account is inactive. Please contact support.',
                'data' => null,
            ], 403);
        }

        $now = Carbon::now();
        $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($deliveryPerson, $otpCode, $now) {
            DeliveryPersonOtp::query()
                ->where('delivery_person_id', $deliveryPerson->id)
                ->where('is_active', 1)
                ->update([
                    'is_active' => 0,
                    'updated_date' => $now,
                ]);

            DeliveryPersonOtp::create([
                'delivery_person_id' => $deliveryPerson->id,
                'otp_code' => $otpCode,
                'otp_expires_at' => $now->copy()->addMinutes(3),
                'is_active' => 1,
                'created_date' => $now,
            ]);
        });

        $smsResponse = app()->environment(['local', 'testing']) && blank(config('services.sms_api.key'))
            ? ['success' => true, 'message' => 'OTP generated for local testing.']
            : $this->otpSmsService->sendOtp(
                $deliveryPerson->mobile,
                $otpCode,
                $deliveryPerson->name
            );

        if (!($smsResponse['success'] ?? false)) {
            return response()->json([
                'status' => false,
                'message' => $smsResponse['message'] ?? 'Failed to send OTP SMS. Please try again.',
                'data' => null,
            ], 500);
        }

        $data = [
            'mobile' => $deliveryPerson->mobile,
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

    /**
     * POST /api/v1/delivery/otp/verify
     *
     * Verify the OTP, log the delivery person in, and return a Sanctum token
     * together with the role so the app knows which UI to show.
     */
    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'mobile' => ['required', 'regex:/^[0-9]{10,15}$/'],
            'otp' => ['required', 'digits:6'],
        ]);

        $mobileNumber = preg_replace('/\D+/', '', $validated['mobile']) ?? '';

        $otp = DeliveryPersonOtp::query()
            ->with('deliveryPerson')
            ->whereHas('deliveryPerson', function ($query) use ($mobileNumber) {
                $query->where('mobile', $mobileNumber)->where('status', 'active');
            })
            ->where('otp_code', $validated['otp'])
            ->where('is_active', 1)
            ->latest('id')
            ->first();

        if (!$otp || !$otp->deliveryPerson) {
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

        $deliveryPerson = $otp->deliveryPerson;
        $now = Carbon::now();

        DB::transaction(function () use ($otp, $now) {
            $otp->update([
                'is_active' => 0,
                'updated_date' => $now,
            ]);
        });

        $token = $deliveryPerson->createToken('flutter-app-delivery')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Delivery person login successful',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'role' => 'delivery_person',
                'delivery_person' => [
                    'id' => $deliveryPerson->id,
                    'name' => $deliveryPerson->name,
                    'mobile' => $deliveryPerson->mobile,
                    'vehicle_type' => $deliveryPerson->vehicle_type,
                    'vehicle_number' => $deliveryPerson->vehicle_number,
                    'status' => $deliveryPerson->status,
                    'availability_status' => $deliveryPerson->availability_status,
                ],
            ],
        ]);
    }

    /**
     * POST /api/v1/delivery/logout
     *
     * Delete the current delivery person token.
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $token = $user->currentAccessToken();

            if ($token) {
                $token->delete();
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Logged out successfully',
            'data' => null,
        ]);
    }

    /**
     * GET /api/v1/delivery/me
     *
     * Return the currently logged-in delivery person's details.
     */
    public function me(Request $request)
    {
        $deliveryPerson = $request->user();

        return response()->json([
            'status' => true,
            'message' => 'Delivery person profile fetched successfully',
            'data' => [
                'role' => 'delivery_person',
                'delivery_person' => [
                    'id' => $deliveryPerson->id,
                    'name' => $deliveryPerson->name,
                    'mobile' => $deliveryPerson->mobile,
                    'email' => $deliveryPerson->email,
                    'vehicle_type' => $deliveryPerson->vehicle_type,
                    'vehicle_number' => $deliveryPerson->vehicle_number,
                    'status' => $deliveryPerson->status,
                    'availability_status' => $deliveryPerson->availability_status,
                ],
            ],
        ]);
    }

}
