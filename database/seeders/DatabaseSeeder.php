<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            CategorySeeder::class,
            OwnerSeeder::class,
        ]);

        // Akun contoh hanya untuk lingkungan pengembangan dan test.
        if (app()->environment(['local', 'testing'])) {
            $this->call(DevUsersSeeder::class);
        }
    }
}
