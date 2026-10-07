<?php

namespace App\Repositories\Contracts;

use App\DataTransferObjects\AdminProductFilter;
use App\DataTransferObjects\CatalogFilter;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{
    public function paginateForCatalog(CatalogFilter $filter): LengthAwarePaginator;

    /** Detail untuk Customer/Kasir, beserta option group aktif dan option-nya. */
    public function findForCatalogOrFail(int $id): Product;

    public function paginateForAdmin(AdminProductFilter $filter): LengthAwarePaginator;

    public function findForAdminOrFail(int $id): Product;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Product;

    /** @param array<string, mixed> $attributes */
    public function update(Product $product, array $attributes): Product;

    public function delete(Product $product): void;

    /** @param array<int, array{sort_order: int}> $groups id group => data pivot */
    public function syncOptionGroups(Product $product, array $groups): void;
}
