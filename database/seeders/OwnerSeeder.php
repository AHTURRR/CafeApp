<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Akun Owner pertama. Di production email dan password WAJIB diberikan lewat environment
 * (OWNER_EMAIL, OWNER_PASSWORD); tidak ada password bawaan.
 */
class OwnerSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('OWNER_EMAIL');
        $password = env('OWNER_PASSWORD');

        if (app()->environment('production') && (! $email || ! $password)) {
            throw new RuntimeException('Set OWNER_EMAIL dan OWNER_PASSWORD sebelum menjalankan seeder di production.');
        }

        // firstOrCreate: menjalankan seeder ulang tidak mereset password Owner.
        User::firstOrCreate(
            ['email' => $email ?: 'owner@cafe.test'],
            [
                'name' => env('OWNER_NAME', 'Owner'),
                'password' => $password ?: 'password',
                'role' => Role::Owner,
                'status' => UserStatus::Active,
            ],
        );
    }
}
