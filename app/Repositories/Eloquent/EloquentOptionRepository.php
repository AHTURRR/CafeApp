<?php

namespace App\Repositories\Eloquent;

use App\Models\Option;
use App\Repositories\Contracts\OptionRepositoryInterface;

final class EloquentOptionRepository implements OptionRepositoryInterface
{
    public function findOrFail(int $id): Option
    {
        return Option::query()->findOrFail($id);
    }

    public function nameExistsInGroup(int $groupId, string $name, ?int $exceptId): bool
    {
        return Option::query()
            ->where('option_group_id', $groupId)
            ->whereRaw('lower(name) = ?', [mb_strtolower(trim($name))])
            ->when($exceptId !== null, fn ($q) => $q->where('id', '!=', $exceptId))
            ->exists();
    }

    public function countDefaults(int $groupId, ?int $exceptId): int
    {
        return Option::query()
            ->where('option_group_id', $groupId)->where('is_default', true)
            ->when($exceptId !== null, fn ($q) => $q->where('id', '!=', $exceptId))
            ->count();
    }

    public function clearDefaults(int $groupId, ?int $exceptId): void
    {
        Option::query()
            ->where('option_group_id', $groupId)->where('is_default', true)
            ->when($exceptId !== null, fn ($q) => $q->where('id', '!=', $exceptId))
            ->update(['is_default' => false]);
    }

    public function create(array $attributes): Option
    {
        return Option::create($attributes);
    }

    public function update(Option $option, array $attributes): Option
    {
        $option->update($attributes);

        return $option->refresh();
    }

    public function delete(Option $option): void
    {
        $option->delete();
    }
}
