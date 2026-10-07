<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Hanya untuk local/testing. Password semua akun: "password". */
class DevUsersSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['Kasir Contoh', 'kasir@cafe.test', Role::Cashier],
            ['Kitchen Contoh', 'kitchen@cafe.test', Role::Kitchen],
            ['Customer Contoh', 'customer@cafe.test', Role::Customer],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            User::firstOrCreate(['email' => $email], [
                'name' => $name,
                'password' => 'password',
                'role' => $role,
                'status' => UserStatus::Active,
            ]);
        }
    }
}
