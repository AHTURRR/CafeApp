<?php

namespace Tests\Concerns;

use App\Enums\Role;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

trait ActsAsRole
{
    protected function actingAsRole(Role $role): User
    {
        $user = User::factory()->role($role)->create();
        Sanctum::actingAs($user);

        return $user;
    }
}
