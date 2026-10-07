<?php

namespace Tests\Feature\Rbac;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Matriks endpoint x role untuk endpoint SUNGGUHAN (API_SPECIFICATION bagian 3.1).
 * Role yang diizinkan tidak boleh mendapat 401/403 (hasilnya 200/404/422 tergantung data);
 * role lain harus mendapat 403 FORBIDDEN. Ketika langkah I-3 dan seterusnya menambah endpoint,
 * tambahkan baris di ENDPOINTS.
 */
class EndpointMatrixTest extends TestCase
{
    use RefreshDatabase;

    private const ALL = ['customer', 'cashier', 'kitchen', 'owner'];

    private const MENU = ['customer', 'cashier', 'owner'];

    private const OWNER = ['owner'];

    /** [method, uri, role yang diizinkan] */
    private const ENDPOINTS = [
        ['GET', '/api/v1/auth/me', self::ALL],
        ['POST', '/api/v1/auth/logout', self::ALL],
        ['PUT', '/api/v1/profile', self::ALL],

        ['GET', '/api/v1/categories', self::MENU],
        ['GET', '/api/v1/products', self::MENU],
        ['GET', '/api/v1/products/1', self::MENU],
        ['GET', '/api/v1/settings/public', self::MENU],

        ['GET', '/api/v1/admin/categories', self::OWNER],
        ['POST', '/api/v1/admin/categories', self::OWNER],
        ['PUT', '/api/v1/admin/categories/1', self::OWNER],
        ['DELETE', '/api/v1/admin/categories/1', self::OWNER],

        ['GET', '/api/v1/admin/products', self::OWNER],
        ['POST', '/api/v1/admin/products', self::OWNER],
        ['GET', '/api/v1/admin/products/1', self::OWNER],
        ['PUT', '/api/v1/admin/products/1', self::OWNER],
        ['DELETE', '/api/v1/admin/products/1', self::OWNER],
        ['PATCH', '/api/v1/admin/products/1/status', self::OWNER],
        ['PUT', '/api/v1/admin/products/1/option-groups', self::OWNER],
        ['POST', '/api/v1/admin/products/1/image', self::OWNER],
        ['DELETE', '/api/v1/admin/products/1/image', self::OWNER],

        ['GET', '/api/v1/admin/customization/groups', self::OWNER],
        ['POST', '/api/v1/admin/customization/groups', self::OWNER],
        ['GET', '/api/v1/admin/customization/groups/1', self::OWNER],
        ['PUT', '/api/v1/admin/customization/groups/1', self::OWNER],
        ['DELETE', '/api/v1/admin/customization/groups/1', self::OWNER],
        ['POST', '/api/v1/admin/customization/options', self::OWNER],
        ['PUT', '/api/v1/admin/customization/options/1', self::OWNER],
        ['DELETE', '/api/v1/admin/customization/options/1', self::OWNER],
    ];

    /** @return array<string, array{string, string, string, bool}> */
    public static function matrix(): array
    {
        $cases = [];
        foreach (self::ENDPOINTS as [$method, $uri, $allowed]) {
            foreach (self::ALL as $role) {
                $cases["$role $method $uri"] = [$method, $uri, $role, in_array($role, $allowed, true)];
            }
        }

        return $cases;
    }

    #[DataProvider('matrix')]
    public function test_role_access(string $method, string $uri, string $role, bool $allowed): void
    {
        Sanctum::actingAs(User::factory()->role(Role::from($role))->create());

        $response = $this->json($method, $uri);

        if ($allowed) {
            $this->assertNotContains($response->getStatusCode(), [401, 403], "$role seharusnya boleh $method $uri");
        } else {
            $response->assertStatus(403)->assertJsonPath('code', 'FORBIDDEN');
        }
    }

    /** @return array<string, array{string, string}> */
    public static function endpoints(): array
    {
        $cases = [];
        foreach (self::ENDPOINTS as [$method, $uri]) {
            $cases["$method $uri"] = [$method, $uri];
        }

        return $cases;
    }

    #[DataProvider('endpoints')]
    public function test_every_endpoint_requires_authentication(string $method, string $uri): void
    {
        $this->json($method, $uri)->assertStatus(401)->assertJsonPath('code', 'UNAUTHENTICATED');
    }
}
