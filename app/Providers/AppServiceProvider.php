<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiters();
    }

    /**
     * Register the named rate limiters used across the app.
     *
     * The counters are stored in the configured cache store. In production
     * CACHE_STORE is "database", so the counters are shared between all
     * requests and stay persistent until they expire.
     */
    protected function configureRateLimiters(): void
    {
        // General API limiter, applied to every /api route. This is a broad
        // safety net; the named limiters below are the strict ones.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(300)
                ->by($request->user() ? (string) $request->user()->id : $request->ip())
                ->response($this->rateLimitResponse('Too many requests. Please try again later.'));
        });

        // OTP sending (login, register and delivery). Limits by mobile number
        // and by IP so a single number or network cannot be SMS bombed.
        RateLimiter::for('otp-send', function (Request $request) {
            $mobile = $this->otpMobile($request);

            return [
                Limit::perMinute(1)
                    ->by('otp-send-mobile:' . $mobile)
                    ->response($this->rateLimitResponse('Please wait a moment before requesting another OTP.')),

                Limit::perDay(10)
                    ->by('otp-send-mobile-day:' . $mobile)
                    ->response($this->rateLimitResponse('Daily OTP limit reached for this mobile number. Please try again tomorrow.')),

                Limit::perMinute(5)
                    ->by('otp-send-ip:' . $request->ip())
                    ->response($this->rateLimitResponse('Too many OTP requests from this network. Please try again later.')),

                Limit::perDay(50)
                    ->by('otp-send-ip-day:' . $request->ip())
                    ->response($this->rateLimitResponse('Daily OTP limit reached from this network. Please try again tomorrow.')),
            ];
        });

        // OTP verification. Limits by mobile number and by IP.
        RateLimiter::for('otp-verify', function (Request $request) {
            $mobile = $this->otpMobile($request);

            return [
                Limit::perMinutes(10, 10)
                    ->by('otp-verify-mobile:' . $mobile)
                    ->response($this->rateLimitResponse('Too many OTP verification attempts for this mobile number. Please request a new OTP.')),

                Limit::perMinute(10)
                    ->by('otp-verify-ip:' . $request->ip())
                    ->response($this->rateLimitResponse('Too many OTP verification attempts. Please try again later.')),

                Limit::perDay(100)
                    ->by('otp-verify-ip-day:' . $request->ip())
                    ->response($this->rateLimitResponse('Daily verification limit reached from this network. Please try again tomorrow.')),
            ];
        });

        // Password login (admin). Keyed by IP only so an attacker cannot lock
        // out a known account by failing logins for that username.
        RateLimiter::for('login', function (Request $request) {
            return [
                Limit::perMinute(10)->by('login-ip:' . $request->ip()),
                Limit::perMinutes(30, 30)->by('login-ip-long:' . $request->ip()),
            ];
        });
    }

    /**
     * Normalize the mobile number from the request for rate limiting.
     */
    protected function otpMobile(Request $request): string
    {
        $raw = (string) ($request->input('mobile_number') ?? $request->input('mobile') ?? '');
        $normalized = preg_replace('/\D+/', '', $raw);

        return $normalized !== '' ? $normalized : 'ip-' . $request->ip();
    }

    /**
     * Build the response returned when a request is rate limited.
     *
     * API requests receive JSON, while browser requests are sent back to the
     * form with an error message.
     */
    protected function rateLimitResponse(string $message): \Closure
    {
        return function (Request $request, array $headers) use ($message) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => $message,
                    'data' => null,
                ], 429, $headers);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', $message)
                ->withErrors([
                    'mobile_number' => $message,
                    'otp' => $message,
                    'otp_code' => $message,
                ]);
        };
    }
}
