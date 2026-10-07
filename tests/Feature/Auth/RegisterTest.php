<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function payload(array $override = []): array
    {
        return array_merge([
            'name' => 'Ahmad',
            'email' => 'Ahmad@Example.com',
            'phone' => '081234567890',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'device_name' => 'Pixel 8',
        ], $override);
    }

    public function test_customer_can_register_and_receives_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('data.user.role', 'customer')
            ->assertJsonPath('data.user.status', 'active')
            ->assertJsonPath('data.user.email', 'ahmad@example.com') // disimpan huruf kecil
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email', 'phone', 'role', 'status'], 'token', 'token_type', 'expires_at']]);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertDatabaseHas('users', ['email' => 'ahmad@example.com', 'role' => 'customer']);
        $this->assertNotSame('rahasia123', User::first()->password, 'password harus di-hash');
    }

    public function test_role_sent_by_client_is_ignored(): void
    {
        $this->postJson('/api/v1/auth/register', $this->payload(['role' => 'owner', 'status' => 'inactive']))
            ->assertCreated()
            ->assertJsonPath('data.user.role', 'customer')
            ->assertJsonPath('data.user.status', 'active');

        $this->assertDatabaseHas('users', ['email' => 'ahmad@example.com', 'role' => 'customer', 'status' => 'active']);
    }

    public function test_token_expires_in_customer_ttl(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->payload());

        $expires = \Carbon\Carbon::parse($response->json('data.expires_at'));
        $this->assertEqualsWithDelta(now()->addDays(30)->timestamp, $expires->timestamp, 120);
    }

    public function test_duplicate_email_is_rejected_case_insensitively(): void
    {
        User::factory()->create(['email' => 'ahmad@example.com']);

        $this->postJson('/api/v1/auth/register', $this->payload(['email' => 'AHMAD@example.com']))
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors('email');
    }

    public function test_email_of_soft_deleted_user_can_be_registered_again(): void
    {
        User::factory()->create(['email' => 'ahmad@example.com'])->delete();

        $this->postJson('/api/v1/auth/register', $this->payload())->assertCreated();
    }

    public function test_validation_errors_use_standard_format(): void
    {
        $this->postJson('/api/v1/auth/register', [])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['message', 'code', 'errors' => ['name', 'email', 'password']]);
    }

    public function test_password_must_be_confirmed_and_long_enough(): void
    {
        $this->postJson('/api/v1/auth/register', $this->payload(['password_confirmation' => 'lain']))
            ->assertStatus(422)->assertJsonValidationErrors('password');

        $this->postJson('/api/v1/auth/register', $this->payload(['password' => 'pendek', 'password_confirmation' => 'pendek']))
            ->assertStatus(422)->assertJsonValidationErrors('password');
    }
}
