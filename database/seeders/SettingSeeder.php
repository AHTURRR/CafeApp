<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Nilai awal contoh. Owner mengubahnya lewat menu Settings (PUT /admin/settings). */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'tax_percent' => 10,
            'service_charge_percent' => 5,
            'cafe_name' => 'CafeApp',
        ];

        foreach ($defaults as $key => $value) {
            // ON CONFLICT DO NOTHING: seeder aman dijalankan ulang tanpa menimpa perubahan Owner.
            DB::table('settings')->insertOrIgnore([
                'key' => $key,
                'value' => json_encode($value),
                'updated_at' => now(),
            ]);
        }
    }
}
