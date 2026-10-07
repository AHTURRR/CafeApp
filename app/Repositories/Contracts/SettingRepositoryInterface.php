<?php

namespace App\Repositories\Contracts;

interface SettingRepositoryInterface
{
    /** @return array<string, mixed> key => nilai (sudah di-decode dari JSONB) */
    public function all(): array;
}
