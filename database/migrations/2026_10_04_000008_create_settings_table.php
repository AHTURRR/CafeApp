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
CREATE TABLE settings (
    key        VARCHAR(100) PRIMARY KEY,
    value      JSONB        NOT NULL,
    updated_by BIGINT       REFERENCES users (id) ON DELETE SET NULL,
    updated_at TIMESTAMPTZ  NOT NULL DEFAULT now()
);
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TABLE IF EXISTS settings CASCADE');
    }
};
