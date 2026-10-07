<?php

namespace App\Repositories\Contracts;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

interface CategoryRepositoryInterface
{
    /** @return Collection<int, Category> kategori aktif, berurutan (untuk katalog) */
    public function activeOrdered(): Collection;

    /** @return Collection<int, Category> semua kategori + products_count (untuk Owner) */
    public function allForAdmin(): Collection;

    public function findOrFail(int $id): Category;

    public function nameExists(string $name, ?int $exceptId): bool;

    public function countProducts(Category $category): int;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Category;

    /** @param array<string, mixed> $attributes */
    public function update(Category $category, array $attributes): Category;

    public function delete(Category $category): void;
}
