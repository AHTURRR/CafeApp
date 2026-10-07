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
CREATE TABLE product_option_groups (
    product_id      BIGINT NOT NULL REFERENCES products (id)      ON DELETE CASCADE,
    option_group_id BIGINT NOT NULL REFERENCES option_groups (id) ON DELETE CASCADE,
    sort_order      INT    NOT NULL DEFAULT 0,
    PRIMARY KEY (product_id, option_group_id)
);

CREATE INDEX product_option_groups_group_idx ON product_option_groups (option_group_id);
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TABLE IF EXISTS product_option_groups CASCADE');
    }
};
