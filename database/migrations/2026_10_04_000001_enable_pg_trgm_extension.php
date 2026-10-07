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
CREATE EXTENSION IF NOT EXISTS pg_trgm;
SQL);
    }

    public function down(): void
    {
        // Ekstensi sengaja tidak dihapus karena dapat dipakai database lain.
    }
};
