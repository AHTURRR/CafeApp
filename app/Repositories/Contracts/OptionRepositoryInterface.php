<?php

namespace App\Repositories\Contracts;

use App\Models\Option;

interface OptionRepositoryInterface
{
    public function findOrFail(int $id): Option;

    public function nameExistsInGroup(int $groupId, string $name, ?int $exceptId): bool;

    public function countDefaults(int $groupId, ?int $exceptId): int;

    public function clearDefaults(int $groupId, ?int $exceptId): void;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Option;

    /** @param array<string, mixed> $attributes */
    public function update(Option $option, array $attributes): Option;

    public function delete(Option $option): void;
}
