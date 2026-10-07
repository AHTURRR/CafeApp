<?php

namespace App\Services\Admin;

use App\DataTransferObjects\AdminProductFilter;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\Storage\ImageStorageInterface;
use App\Support\ProductStockPolicy;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly ImageStorageInterface $images,
    ) {
    }

    public function paginate(AdminProductFilter $filter): LengthAwarePaginator
    {
        return $this->products->paginateForAdmin($filter);
    }

    public function find(int $id): Product
    {
        return $this->products->findForAdminOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $stock = isset($data['stock']) ? (int) $data['stock'] : null;
            $status = ProductStatus::tryFrom((string) ($data['status'] ?? '')) ?? ProductStatus::Available;

            $product = $this->products->create([
                'category_id' => (int) $data['category_id'],
                'name' => trim((string) $data['name']),
                'description' => $data['description'] ?? null,
                'price' => (int) $data['price'],
                'stock' => $stock,
                'status' => ProductStockPolicy::resolveStatus($status, $stock),
            ]);

            $this->products->syncOptionGroups($product, $this->syncMap($data['option_group_ids'] ?? []));

            return $this->products->findForAdminOrFail($product->id);
        });
    }

    /**
     * PUT: category_id, name, price wajib. Field opsional yang tidak dikirim tidak diubah.
     * option_group_ids hanya disinkronkan bila dikirim (daftar kosong = lepas semua group).
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Product
    {
        return DB::transaction(function () use ($id, $data) {
            $product = $this->products->findForAdminOrFail($id);

            $stock = $product->stock;
            $attributes = [
                'category_id' => (int) $data['category_id'],
                'name' => trim((string) $data['name']),
                'price' => (int) $data['price'],
            ];

            if (array_key_exists('description', $data)) {
                $attributes['description'] = $data['description'];
            }
            if (array_key_exists('stock', $data)) {
                $stock = $data['stock'] === null ? null : (int) $data['stock'];
                $attributes['stock'] = $stock;
            }

            $status = isset($data['status']) ? ProductStatus::from($data['status']) : $product->status;
            $attributes['status'] = ProductStockPolicy::resolveStatus($status, $stock);

            $this->products->update($product, $attributes);

            if (isset($data['option_group_ids'])) {
                $this->products->syncOptionGroups($product, $this->syncMap($data['option_group_ids']));
            }

            return $this->products->findForAdminOrFail($id);
        });
    }

    public function updateStatus(int $id, ProductStatus $status): Product
    {
        $product = $this->products->findForAdminOrFail($id);

        if ($status === ProductStatus::Available && ! ProductStockPolicy::canBeAvailable($product->stock)) {
            throw ValidationException::withMessages([
                'status' => ['Stok produk 0. Tambah stok terlebih dahulu sebelum mengubah status menjadi AVAILABLE.'],
            ]);
        }

        $this->products->update($product, ['status' => $status]);

        return $this->products->findForAdminOrFail($id);
    }

    /** @param list<array{id: int, sort_order?: int|null}> $groups */
    public function syncOptionGroups(int $id, array $groups): Product
    {
        $product = $this->products->findForAdminOrFail($id);

        $map = [];
        foreach (array_values($groups) as $index => $group) {
            $map[(int) $group['id']] = ['sort_order' => (int) ($group['sort_order'] ?? $index + 1)];
        }

        $this->products->syncOptionGroups($product, $map);

        return $this->products->findForAdminOrFail($id);
    }

    public function delete(int $id): void
    {
        // Soft delete: order lama tetap utuh karena menyimpan snapshot.
        $this->products->delete($this->products->findForAdminOrFail($id));
    }

    public function uploadImage(int $id, UploadedFile $file): Product
    {
        $product = $this->products->findForAdminOrFail($id);
        $oldPublicId = $product->image_public_id;

        $stored = $this->images->store($file, 'products');

        try {
            $this->products->update($product, ['image_url' => $stored->url, 'image_public_id' => $stored->publicId]);
        } catch (Throwable $e) {
            $this->images->delete($stored->publicId);   // jangan tinggalkan berkas yatim
            throw $e;
        }

        $this->images->delete($oldPublicId);

        return $this->products->findForAdminOrFail($id);
    }

    public function deleteImage(int $id): void
    {
        $product = $this->products->findForAdminOrFail($id);

        $this->images->delete($product->image_public_id);
        $this->products->update($product, ['image_url' => null, 'image_public_id' => null]);
    }

    /**
     * @param  list<int|string>  $ids
     * @return array<int, array{sort_order: int}>
     */
    private function syncMap(array $ids): array
    {
        $map = [];
        foreach (array_values($ids) as $index => $groupId) {
            $map[(int) $groupId] = ['sort_order' => $index + 1];
        }

        return $map;
    }
}
