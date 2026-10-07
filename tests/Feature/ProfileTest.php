<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_name_and_phone(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile', ['name' => 'Nama Baru', 'phone' => '089900001111'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nama Baru')
            ->assertJsonPath('data.phone', '089900001111');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Nama Baru']);
    }

    public function test_role_status_and_email_cannot_be_changed_through_profile(): void
    {
        $user = User::factory()->create(['email' => 'asli@cafe.test']);
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile', ['name' => 'X', 'role' => 'owner', 'status' => 'inactive', 'email' => 'lain@cafe.test'])
            ->assertOk();

        $fresh = $user->fresh();
        $this->assertSame('customer', $fresh->role->value);
        $this->assertSame('active', $fresh->status->value);
        $this->assertSame('asli@cafe.test', $fresh->email);
    }

    public function test_invalid_phone_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/v1/profile', ['phone' => 'abc'])
            ->assertStatus(422)->assertJsonValidationErrors('phone');
    }

    public function test_profile_requires_authentication(): void
    {
        $this->putJson('/api/v1/profile', ['name' => 'X'])->assertStatus(401);
    }
}
