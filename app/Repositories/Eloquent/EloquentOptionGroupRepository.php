<?php

namespace App\Repositories\Eloquent;

use App\Models\OptionGroup;
use App\Repositories\Contracts\OptionGroupRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentOptionGroupRepository implements OptionGroupRepositoryInterface
{
    public function allForAdmin(): Collection
    {
        return OptionGroup::query()
            ->with('options')->withCount('products')
            ->orderBy('sort_order')->orderBy('name')->get();
    }

    public function findOrFail(int $id): OptionGroup
    {
        return OptionGroup::query()->findOrFail($id);
    }

    public function findForAdminOrFail(int $id): OptionGroup
    {
        return OptionGroup::query()->with('options')->withCount('products')->findOrFail($id);
    }

    public function countProducts(OptionGroup $group): int
    {
        return $group->products()->count();   // hanya produk yang belum dihapus
    }

    public function create(array $attributes): OptionGroup
    {
        return OptionGroup::create($attributes);
    }

    public function update(OptionGroup $group, array $attributes): OptionGroup
    {
        $group->update($attributes);

        return $group->refresh();
    }

    public function deleteWithOptions(OptionGroup $group): void
    {
        DB::transaction(function () use ($group) {
            $group->options()->delete();   // soft delete massal (model memakai SoftDeletes)
            $group->delete();
        });
    }
}
