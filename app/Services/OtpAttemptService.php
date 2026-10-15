<?php

namespace App\Services;

use Illuminate\Support\Facades\RateLimiter;

class OtpAttemptService
{
    /**
     * Number of wrong OTP codes allowed before the challenge is invalidated.
     */
    public const MAX_ATTEMPTS = 5;

    /**
     * How long (in seconds) the failed attempts are remembered.
     */
    public const DECAY_SECONDS = 600;

    /**
     * Record a wrong OTP attempt.
     *
     * Returns true when the allowed number of attempts has been reached.
     */
    public function recordFailure(string $context, string $identifier): bool
    {
        $attempts = RateLimiter::hit($this->key($context, $identifier), self::DECAY_SECONDS);

        return $attempts >= self::MAX_ATTEMPTS;
    }

    /**
     * Forget the recorded failures for a context and identifier.
     */
    public function clear(string $context, string $identifier): void
    {
        RateLimiter::clear($this->key($context, $identifier));
    }

    /**
     * Build the cache key used to count failed attempts.
     */
    protected function key(string $context, string $identifier): string
    {
        return 'otp-attempt:' . $context . ':' . $identifier;
    }
}
