<?php

namespace Tests\Feature\Rbac;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Menguji middleware 'role:' dengan route khusus test. Pola yang sama (matriks endpoint x role)
 * dipakai pada langkah berikutnya untuk endpoint sungguhan (API_SPECIFICATION bagian 3.1).
 */
class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /** endpoint => role yang diizinkan */
    private const ROUTES = [
        'customer' => ['customer'],
        'cashier' => ['cashier'],
        'kitchen' => ['kitchen'],
        'owner' => ['owner'],
        'staff' => ['cashier', 'kitchen', 'owner'],
        'catalog' => ['customer', 'cashier', 'owner'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth:sanctum', 'active'])->prefix('api/v1/__rbac')->group(function () {
            foreach (self::ROUTES as $name => $roles) {
                Route::get($name, fn () => response()->json(['ok' => true]))
                    ->middleware('role:'.implode(',', $roles));
            }
        });
    }

    /** @return array<string, array{string, string, int}> */
    public static function matrix(): array
    {
        $cases = [];
        foreach (self::ROUTES as $endpoint => $allowed) {
            foreach (Role::values() as $role) {
                $cases["$endpoint sebagai $role"] = [$endpoint, $role, in_array($role, $allowed, true) ? 200 : 403];
            }
        }

        return $cases;
    }

    #[DataProvider('matrix')]
    public function test_role_matrix(string $endpoint, string $role, int $expected): void
    {
        Sanctum::actingAs(User::factory()->role(Role::from($role))->create());

        $response = $this->getJson("/api/v1/__rbac/$endpoint");

        $response->assertStatus($expected);
        if ($expected === 403) {
            $response->assertJsonPath('code', 'FORBIDDEN');
        }
    }

    public function test_unauthenticated_request_gets_401_on_every_endpoint(): void
    {
        foreach (array_keys(self::ROUTES) as $endpoint) {
            $this->getJson("/api/v1/__rbac/$endpoint")
                ->assertStatus(401)->assertJsonPath('code', 'UNAUTHENTICATED');
        }
    }

    public function test_inactive_staff_is_blocked_before_role_check(): void
    {
        Sanctum::actingAs(User::factory()->owner()->inactive()->create());

        $this->getJson('/api/v1/__rbac/owner')
            ->assertStatus(403)->assertJsonPath('code', 'ACCOUNT_INACTIVE');
    }

    public function test_role_change_takes_effect_immediately_without_new_token(): void
    {
        $user = User::factory()->customer()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/__rbac/owner')->assertStatus(403);

        $user->update(['role' => Role::Owner]);
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/v1/__rbac/owner')->assertOk();
    }
}
