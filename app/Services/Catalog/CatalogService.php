<?php

namespace App\Services\Catalog;

use App\DataTransferObjects\CatalogFilter;
use App\Models\Category;
use App\Models\Product;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/** Pembacaan katalog untuk Customer, Kasir, dan Owner. Hanya yang tampil di menu. */
final class CatalogService
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categories,
        private readonly ProductRepositoryInterface $products,
    ) {
    }

    /** @return Collection<int, Category> */
    public function categories(): Collection
    {
        return $this->categories->activeOrdered();
    }

    public function products(CatalogFilter $filter): LengthAwarePaginator
    {
        return $this->products->paginateForCatalog($filter);
    }

    public function product(int $id): Product
    {
        return $this->products->findForCatalogOrFail($id);
    }
}
