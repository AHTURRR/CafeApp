<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    public function activeOrdered(): Collection
    {
        return Category::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
    }

    public function allForAdmin(): Collection
    {
        return Category::query()->withCount('products')->orderBy('sort_order')->orderBy('name')->get();
    }

    public function findOrFail(int $id): Category
    {
        return Category::query()->findOrFail($id);
    }

    public function nameExists(string $name, ?int $exceptId): bool
    {
        return Category::query()
            ->whereRaw('lower(name) = ?', [mb_strtolower(trim($name))])
            ->when($exceptId !== null, fn ($q) => $q->where('id', '!=', $exceptId))
            ->exists();
    }

    public function countProducts(Category $category): int
    {
        return $category->products()->count();   // SoftDeletes: produk yang sudah dihapus tidak dihitung
    }

    public function create(array $attributes): Category
    {
        return Category::create($attributes);
    }

    public function update(Category $category, array $attributes): Category
    {
        $category->update($attributes);

        return $category->refresh();
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }
}
