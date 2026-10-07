<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\SettingRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class EloquentSettingRepository implements SettingRepositoryInterface
{
    public function all(): array
    {
        return DB::table('settings')->pluck('value', 'key')
            ->map(fn ($json) => json_decode((string) $json, true))
            ->all();
    }
}
