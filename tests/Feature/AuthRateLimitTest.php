<?php

namespace Tests\Feature;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthRateLimitTest extends TestCase
{
    /**
     * The test suite uses an in-memory SQLite database and the "array" cache
     * store (see phpunit.xml) so these tests never touch real data.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->createAuthTables();
    }

    /**
     * Create only the tables needed by the authentication endpoints.
     */
    protected function createAuthTables(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('verified_status')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });

        Schema::create('customer_register_otp', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('otp_code')->nullable();
            $table->dateTime('otp_expires_at')->nullable();
            $table->boolean('is_active')->default(1);
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->dateTime('created_date')->nullable();
            $table->dateTime('updated_date')->nullable();
        });

        Schema::create('delivery_persons', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('mobile')->nullable();
            $table->string('status')->nullable();
            $table->string('availability_status')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_person_otp', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_person_id')->nullable();
            $table->string('otp_code')->nullable();
            $table->dateTime('otp_expires_at')->nullable();
            $table->boolean('is_active')->default(1);
            $table->dateTime('created_date')->nullable();
            $table->dateTime('updated_date')->nullable();
        });

        Schema::create('admin_login', function (Blueprint $table) {
            $table->id();
            $table->string('admin_username')->nullable();
            $table->string('admin_password')->nullable();
            $table->string('user_type')->nullable();
            $table->string('cur_status')->nullable();
            $table->string('active_session_id')->nullable();
            $table->dateTime('last_login_at')->nullable();
        });
    }

    public function test_excessive_otp_send_requests_are_rate_limited(): void
    {
        // First request is allowed through to the controller (404 = not registered).
        $this->postJson('/api/v1/otp/send', ['mobile_number' => '9000000001'])
            ->assertStatus(404);

        // The per-mobile cooldown now rejects the second request.
        $this->postJson('/api/v1/otp/send', ['mobile_number' => '9000000001'])
            ->assertStatus(429)
            ->assertJson([
                'status' => false,
            ])
            ->assertJsonStructure(['status', 'message', 'data']);
    }

    public function test_otp_send_allows_first_request_within_limit(): void
    {
        $this->postJson('/api/v1/otp/send', ['mobile_number' => '9000000002'])
            ->assertStatus(404);
    }

    public function test_repeated_incorrect_otps_are_blocked_and_invalidated(): void
    {
        $payload = ['mobile_number' => '9111111111', 'otp' => '000000'];

        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $this->postJson('/api/v1/otp/verify', $payload)->assertStatus(401);
        }

        // The fifth wrong attempt reaches the allowed limit.
        $this->postJson('/api/v1/otp/verify', $payload)
            ->assertStatus(429)
            ->assertJson([
                'status' => false,
                'message' => 'Too many invalid OTP attempts. Please request a new OTP.',
            ]);
    }

    public function test_otp_verify_allows_attempt_within_limit(): void
    {
        $this->postJson('/api/v1/otp/verify', [
            'mobile_number' => '9222222222',
            'otp' => '000000',
        ])->assertStatus(401);
    }

    public function test_excessive_login_attempts_are_rate_limited(): void
    {
        $credentials = ['username' => 'nobody', 'password' => 'wrong-password'];

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->from('/admin/login')
                ->post('/admin/login', $credentials)
                ->assertStatus(302);
        }

        $this->from('/admin/login')
            ->post('/admin/login', $credentials)
            ->assertStatus(429);
    }

    public function test_configured_rate_limiters_are_registered(): void
    {
        $this->assertNotNull(RateLimiter::limiter('api'));
        $this->assertNotNull(RateLimiter::limiter('otp-send'));
        $this->assertNotNull(RateLimiter::limiter('otp-verify'));
        $this->assertNotNull(RateLimiter::limiter('login'));

        $apiLimit = (RateLimiter::limiter('api'))(Request::create('/api/v1/home'));

        $this->assertInstanceOf(Limit::class, $apiLimit);
        $this->assertSame(300, $apiLimit->maxAttempts);

        $sendLimits = (RateLimiter::limiter('otp-send'))(Request::create('/api/v1/otp/send', 'POST', [
            'mobile_number' => '9000000003',
        ]));

        $this->assertIsArray($sendLimits);
        $this->assertCount(4, $sendLimits);
    }
}
