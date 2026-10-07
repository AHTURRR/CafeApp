<?php

namespace App\Services\Admin;

use App\Exceptions\ResourceInUseException;
use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

final class CategoryService
{
    public function __construct(private readonly CategoryRepositoryInterface $categories)
    {
    }

    /** @return Collection<int, Category> */
    public function list(): Collection
    {
        return $this->categories->allForAdmin();
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Category
    {
        $name = trim((string) $data['name']);
        $this->assertNameFree($name, null);

        return $this->categories->create([
            'name' => $name,
            'description' => $data['description'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }

    /** PUT: field opsional yang tidak dikirim tidak diubah. @param array<string, mixed> $data */
    public function update(int $id, array $data): Category
    {
        $category = $this->categories->findOrFail($id);
        $name = trim((string) $data['name']);
        $this->assertNameFree($name, $category->id);

        return $this->categories->update($category, [
            'name' => $name,
            'description' => array_key_exists('description', $data) ? $data['description'] : $category->description,
            'sort_order' => isset($data['sort_order']) ? (int) $data['sort_order'] : $category->sort_order,
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : $category->is_active,
        ]);
    }

    public function delete(int $id): void
    {
        $category = $this->categories->findOrFail($id);

        if ($this->categories->countProducts($category) > 0) {
            throw new ResourceInUseException(
                'Kategori masih memiliki produk. Pindahkan atau hapus produknya terlebih dahulu, atau nonaktifkan kategori.'
            );
        }

        $this->categories->delete($category);
    }

    private function assertNameFree(string $name, ?int $exceptId): void
    {
        if ($this->categories->nameExists($name, $exceptId)) {
            throw ValidationException::withMessages(['name' => ['Nama kategori sudah dipakai.']]);
        }
    }
}
