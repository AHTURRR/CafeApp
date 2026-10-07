<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->kitchen()->create();

        $this->withToken($this->tokenFor($user))
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.role', 'kitchen');
    }

    public function test_request_without_token_gets_standard_401(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('code', 'UNAUTHENTICATED')
            ->assertJsonStructure(['message', 'code']);
    }

    public function test_invalid_token_gets_401(): void
    {
        $this->withToken('999|tokenpalsu')->getJson('/api/v1/auth/me')
            ->assertStatus(401)->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();
        $this->assertSame(0, $user->tokens()->count());

        // Guard menyimpan user per proses; reset agar request berikutnya benar-benar mengevaluasi ulang token.
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_logout_only_revokes_the_current_device(): void
    {
        $user = User::factory()->create();
        $phone = $this->tokenFor($user);
        $this->tokenFor($user); // perangkat kedua

        $this->withToken($phone)->postJson('/api/v1/auth/logout')->assertNoContent();

        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_deactivated_user_is_blocked_immediately_even_with_valid_token(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $user->update(['status' => 'inactive']);

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertStatus(403)->assertJsonPath('code', 'ACCOUNT_INACTIVE');
    }

    public function test_expired_token_is_rejected(): void
    {
        // Mengandalkan pemeriksaan kolom expires_at oleh Sanctum.
        $user = User::factory()->create();
        $token = $user->createToken('lama', ['*'], now()->subMinute())->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertStatus(401)->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_unknown_route_returns_standard_404_json(): void
    {
        $this->getJson('/api/v1/tidak-ada')
            ->assertStatus(404)->assertJsonPath('code', 'NOT_FOUND');
    }
}
