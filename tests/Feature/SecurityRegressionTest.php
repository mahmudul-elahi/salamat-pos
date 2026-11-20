<?php

namespace Tests\Feature;

use App\Models\Common\User\UserProfile;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('invitation_token')->nullable();
            $table->string('status')->default('invited');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->nullable();
            $table->decimal('grand_total', 10, 2)->default(0);
            $table->string('payment_status')->nullable();
            $table->integer('status')->default(2);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_admin_settings_and_email_settings_require_authentication(): void
    {
        $response = $this->getJson('/api/admin/email-settings');
        $response->assertStatus(401);

        $response = $this->getJson('/api/admin/settings');
        $response->assertStatus(401);
    }

    public function test_login_returns_generic_error_and_does_not_reveal_user_existence(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Invalid credentials',
        ]);
    }

    public function test_user_join_token_expiration_logic(): void
    {
        // 1. Expired token (created 5 days ago)
        $expiredProfile = new UserProfile([
            'invitation_token' => 'expired_token',
            'status' => 'invited',
        ]);
        $expiredProfile->created_at = Carbon::now()->subDays(5);
        $expiredProfile->updated_at = Carbon::now()->subDays(5);
        $expiredProfile->saveQuietly();

        $response = $this->getJson('/api/auth/available-token/expired_token');
        $response->assertJson([
            'message' => 'Invitation expired',
        ]);

        // 2. Active token (created 1 day ago)
        $validProfile = new UserProfile([
            'invitation_token' => 'valid_token',
            'status' => 'invited',
        ]);
        $validProfile->created_at = Carbon::now()->subDays(1);
        $validProfile->updated_at = Carbon::now()->subDays(1);
        $validProfile->saveQuietly();

        $response = $this->getJson('/api/auth/available-token/valid_token');
        $response->assertJson([
            'message' => 'Token is available',
        ]);
    }

    public function test_sslcommerz_ipn_does_not_dump_and_returns_json(): void
    {
        $response = $this->postJson('/api/ipn', []);
        $response->assertStatus(400);
        $response->assertJson(['message' => 'Transaction ID missing']);

        $response = $this->postJson('/api/ipn', ['tran_id' => 'non_existent_tx']);
        $response->assertStatus(404);
        $response->assertJson(['message' => 'Order not found with this transaction id']);
    }

    public function test_payrun_total_days_calculation_with_day_boundaries(): void
    {
        $start = Carbon::parse('2026-05-01 00:00:00');
        $end = Carbon::parse('2026-05-31 23:59:59.999');

        $totalDays = $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1;

        $this->assertEquals(31, $totalDays);

        $weekStart = Carbon::parse('2026-05-04 00:00:00');
        $weekEnd = Carbon::parse('2026-05-10 23:59:59.999');

        $weekDays = $weekStart->copy()->startOfDay()->diffInDays($weekEnd->copy()->startOfDay()) + 1;

        $this->assertEquals(7, $weekDays);
    }
}
