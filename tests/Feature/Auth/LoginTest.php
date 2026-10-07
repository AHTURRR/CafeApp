<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function login(string $email, string $password = 'password'): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => $password,
            'device_name' => 'Tablet Kasir',
        ]);
    }

    public function test_user_can_login_and_role_is_returned(): void
    {
        User::factory()->cashier()->create(['email' => 'kasir@cafe.test']);

        $this->login('kasir@cafe.test')
            ->assertOk()
            ->assertJsonPath('data.user.role', 'cashier')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure(['data' => ['user', 'token', 'token_type', 'expires_at']]);
    }

    public function test_email_is_case_insensitive_on_login(): void
    {
        User::factory()->create(['email' => 'budi@cafe.test']);

        $this->login('BUDI@Cafe.Test')->assertOk();
    }

    public function test_staff_token_lives_7_days_and_customer_token_30_days(): void
    {
        User::factory()->cashier()->create(['email' => 'kasir@cafe.test']);
        User::factory()->customer()->create(['email' => 'cust@cafe.test']);

        $staff = \Carbon\Carbon::parse($this->login('kasir@cafe.test')->json('data.expires_at'));
        $customer = \Carbon\Carbon::parse($this->login('cust@cafe.test')->json('data.expires_at'));

        $this->assertEqualsWithDelta(now()->addDays(7)->timestamp, $staff->timestamp, 120);
        $this->assertEqualsWithDelta(now()->addDays(30)->timestamp, $customer->timestamp, 120);
    }

    public function test_wrong_password_and_unknown_email_return_identical_error(): void
    {
        User::factory()->create(['email' => 'ada@cafe.test']);

        $wrongPassword = $this->login('ada@cafe.test', 'salah');
        $unknownEmail = $this->login('tidakada@cafe.test');

        $wrongPassword->assertStatus(401)->assertJsonPath('code', 'INVALID_CREDENTIALS');
        $unknownEmail->assertStatus(401)->assertJsonPath('code', 'INVALID_CREDENTIALS');
        $this->assertSame($wrongPassword->json('message'), $unknownEmail->json('message'));
    }

    public function test_inactive_account_is_rejected_only_after_correct_password(): void
    {
        User::factory()->inactive()->create(['email' => 'off@cafe.test']);

        $this->login('off@cafe.test')->assertStatus(403)->assertJsonPath('code', 'ACCOUNT_INACTIVE');

        // Password salah: tidak membocorkan bahwa akun nonaktif.
        $this->login('off@cafe.test', 'salah')->assertStatus(401)->assertJsonPath('code', 'INVALID_CREDENTIALS');
    }

    public function test_soft_deleted_user_cannot_login(): void
    {
        User::factory()->create(['email' => 'hapus@cafe.test'])->delete();

        $this->login('hapus@cafe.test')->assertStatus(401);
    }

    public function test_login_updates_last_login_and_creates_a_token_with_expiry(): void
    {
        $user = User::factory()->create(['email' => 'log@cafe.test']);
        $this->assertNull($user->last_login_at);

        $this->login('log@cafe.test')->assertOk();

        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertNotNull($user->tokens()->first()->expires_at);
    }

    public function test_login_requires_device_name(): void
    {
        $this->postJson('/api/v1/auth/login', ['email' => 'a@b.test', 'password' => 'x'])
            ->assertStatus(422)->assertJsonValidationErrors('device_name');
    }

    public function test_login_is_throttled_after_five_attempts(): void
    {
        User::factory()->create(['email' => 'brute@cafe.test']);

        for ($i = 0; $i < 5; $i++) {
            $this->login('brute@cafe.test', 'salah')->assertStatus(401);
        }

        $this->login('brute@cafe.test', 'salah')
            ->assertStatus(429)
            ->assertJsonPath('code', 'TOO_MANY_REQUESTS')
            ->assertHeader('Retry-After');
    }
}
