<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '08'.fake()->numerify('##########'),
            'password' => 'password', // di-hash oleh cast 'hashed' pada model
            'role' => Role::Customer,
            'status' => UserStatus::Active,
        ];
    }

    public function role(Role $role): static
    {
        return $this->state(fn () => ['role' => $role]);
    }

    public function customer(): static
    {
        return $this->role(Role::Customer);
    }

    public function cashier(): static
    {
        return $this->role(Role::Cashier);
    }

    public function kitchen(): static
    {
        return $this->role(Role::Kitchen);
    }

    public function owner(): static
    {
        return $this->role(Role::Owner);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => UserStatus::Inactive]);
    }
}
