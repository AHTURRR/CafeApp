<?php

namespace App\Repositories\Contracts;

use App\Models\OptionGroup;
use Illuminate\Database\Eloquent\Collection;

interface OptionGroupRepositoryInterface
{
    /** @return Collection<int, OptionGroup> beserta options dan products_count */
    public function allForAdmin(): Collection;

    public function findOrFail(int $id): OptionGroup;

    public function findForAdminOrFail(int $id): OptionGroup;

    public function countProducts(OptionGroup $group): int;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): OptionGroup;

    /** @param array<string, mixed> $attributes */
    public function update(OptionGroup $group, array $attributes): OptionGroup;

    /** Soft delete group beserta seluruh option-nya dalam satu transaksi. */
    public function deleteWithOptions(OptionGroup $group): void;
}
