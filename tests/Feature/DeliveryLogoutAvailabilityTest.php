<?php

namespace Tests\Feature;

use App\Models\DeliveryPerson;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DeliveryLogoutAvailabilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTables();
    }

    protected function createTables(): void
    {
        Schema::create('delivery_persons', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('vehicle_type')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->string('status')->default('active');
            $table->string('availability_status')->default('offline');
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->dateTime('created_date')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->dateTime('updated_date')->nullable();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('tokenable_type');
            $table->unsignedBigInteger('tokenable_id');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['tokenable_type', 'tokenable_id']);
        });
    }

    protected function makeDeliveryPerson(string $availability): DeliveryPerson
    {
        return DeliveryPerson::create([
            'name' => 'Test Rider',
            'mobile' => '9333333333',
            'password' => bcrypt('secret'),
            'status' => 'active',
            'availability_status' => $availability,
        ]);
    }

    public function test_logout_marks_available_delivery_person_as_offline(): void
    {
        $person = $this->makeDeliveryPerson('available');
        $token = $person->createToken('flutter-app-delivery')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/delivery/logout')
            ->assertStatus(200)
            ->assertJson(['status' => true]);

        $this->assertSame('offline', $person->fresh()->availability_status);
    }

    public function test_logout_keeps_busy_delivery_person_unchanged(): void
    {
        $person = $this->makeDeliveryPerson('busy');
        $token = $person->createToken('flutter-app-delivery')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/delivery/logout')
            ->assertStatus(200);

        $this->assertSame('busy', $person->fresh()->availability_status);
    }
}
