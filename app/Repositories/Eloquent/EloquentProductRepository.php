<?php

namespace App\Repositories\Eloquent;

use App\DataTransferObjects\AdminProductFilter;
use App\DataTransferObjects\CatalogFilter;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Support\Like;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class EloquentProductRepository implements ProductRepositoryInterface
{
    /** Status order yang dihitung sebagai penjualan (ERD.md bagian 10 poin 3). */
    private const SALES_STATUSES = ['PAID', 'CONFIRMED', 'PREPARING', 'READY', 'COMPLETED'];

    private const ADMIN_SORTABLE = ['name', 'price', 'stock', 'created_at', 'updated_at'];

    public function paginateForCatalog(CatalogFilter $filter): LengthAwarePaginator
    {
        $query = Product::query()->visibleInCatalog()->with('category:id,name');

        if ($filter->categoryId !== null) {
            $query->where('products.category_id', $filter->categoryId);
        }
        if ($filter->status !== null) {
            $query->where('products.status', $filter->status->value);
        }
        if ($filter->q !== null) {
            $query->where('products.name', 'ILIKE', Like::contains($filter->q));
        }

        if ($filter->popular) {
            $sales = DB::table('order_items as oi')
                ->join('orders as o', 'o.id', '=', 'oi.order_id')
                ->whereIn('o.status', self::SALES_STATUSES)
                ->groupBy('oi.product_id')
                ->selectRaw('oi.product_id, SUM(oi.quantity) AS sold_qty');

            $query->leftJoinSub($sales, 'sales', 'sales.product_id', '=', 'products.id')
                ->orderByRaw('COALESCE(sales.sold_qty, 0) DESC');
        }

        $query->orderBy('products.name')->orderBy('products.id');

        return $query->paginate($filter->perPage, ['products.*'], 'page', $filter->page);
    }

    public function findForCatalogOrFail(int $id): Product
    {
        return Product::query()
            ->visibleInCatalog()
            ->with([
                'category:id,name',
                'optionGroups' => fn ($q) => $q->where('option_groups.is_active', true)->with('options'),
            ])
            ->findOrFail($id);
    }

    public function paginateForAdmin(AdminProductFilter $filter): LengthAwarePaginator
    {
        $query = Product::query()->with('category:id,name');

        if ($filter->categoryId !== null) {
            $query->where('products.category_id', $filter->categoryId);
        }
        if ($filter->status !== null) {
            $query->where('products.status', $filter->status->value);
        }
        if ($filter->q !== null) {
            $query->where('products.name', 'ILIKE', Like::contains($filter->q));
        }

        $desc = str_starts_with($filter->sort, '-');
        $column = ltrim($filter->sort, '-');
        if (! in_array($column, self::ADMIN_SORTABLE, true)) {
            [$column, $desc] = ['created_at', true];
        }

        $query->orderBy("products.$column", $desc ? 'desc' : 'asc')->orderBy('products.id');

        return $query->paginate($filter->perPage, ['products.*'], 'page', $filter->page);
    }

    public function findForAdminOrFail(int $id): Product
    {
        return Product::query()->with(['category:id,name', 'optionGroups'])->findOrFail($id);
    }

    public function create(array $attributes): Product
    {
        return Product::create($attributes);
    }

    public function update(Product $product, array $attributes): Product
    {
        $product->update($attributes);

        return $product->refresh();
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    public function syncOptionGroups(Product $product, array $groups): void
    {
        $product->optionGroups()->sync($groups);
    }
}
