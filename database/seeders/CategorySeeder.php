<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Coffee', 'Non Coffee', 'Food', 'Snack', 'Dessert'] as $i => $name) {
            $exists = DB::table('categories')->whereRaw('lower(name) = ?', [mb_strtolower($name)])->whereNull('deleted_at')->exists();

            if (! $exists) {
                DB::table('categories')->insert([
                    'name' => $name,
                    'sort_order' => $i + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
