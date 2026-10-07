<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * Skema mengacu pada DATABASE_SCHEMA.md (bagian 3). Ditulis sebagai SQL karena CHECK constraint,
 * kolom GENERATED, dan index parsial tidak tersedia di Schema Builder Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE order_counters (
    business_date DATE PRIMARY KEY,
    last_number   INT  NOT NULL DEFAULT 0
);
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TABLE IF EXISTS order_counters CASCADE');
    }
};
