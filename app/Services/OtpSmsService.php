<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class OtpSmsService
{
    public function sendOtp(string $mobileNumber, string $otp, string $sender = 'JC MART'): array
    {
        $apiKey = (string) config('services.sms_api.key');
        $apiUrl = (string) config('services.sms_api.url');

        if ($apiKey === '' || $apiUrl === '') {
            return [
                'success' => false,
                'message' => 'SMS API configuration missing.',
            ];
        }

        $ch = curl_init($apiUrl . '?endpoint=send');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-API-Key: ' . $apiKey,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'number' => $mobileNumber,
            'otp' => $otp,
            'sender' => $sender,
        ]));

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            Log::error('SMS API curl failure', [
                'mobile' => $mobileNumber,
                'error' => $curlError,
            ]);
            return [
                'success' => false,
                'message' => $curlError !== '' ? $curlError : 'Unable to connect to SMS provider.',
            ];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            $plain = trim((string) $response);
            $plainSuccess = preg_match('/\b(success|sent|queued|delivered|ok)\b/i', $plain) === 1;

            Log::info('SMS API non-JSON response', [
                'mobile' => $mobileNumber,
                'http_code' => $httpCode,
                'response' => $plain,
            ]);

            return [
                'success' => $plainSuccess && $httpCode >= 200 && $httpCode < 300,
                'message' => $plain !== '' ? $plain : 'Invalid SMS API response.',
            ];
        }

        $statusValue = $decoded['status'] ?? null;
        $successValue = $decoded['success'] ?? null;
        $isSuccess =
            $successValue === true
            || $successValue === 1
            || (is_string($successValue) && in_array(strtolower($successValue), ['1', 'true', 'success', 'ok', 'sent', 'queued'], true))
            || $statusValue === true
            || $statusValue === 1
            || (is_string($statusValue) && in_array(strtolower($statusValue), ['1', 'true', 'success', 'ok', 'sent', 'queued'], true));

        Log::info('SMS API response', [
            'mobile' => $mobileNumber,
            'http_code' => $httpCode,
            'success' => $isSuccess,
            'response' => $decoded,
        ]);

        return [
            'success' => $isSuccess,
            'message' => (string) ($decoded['message'] ?? ($isSuccess ? 'OTP sent successfully.' : 'SMS sending failed.')),
            'raw' => $decoded,
        ];
    }
}
