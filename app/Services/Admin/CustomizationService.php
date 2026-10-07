<?php

namespace App\Services\Admin;

use App\Exceptions\ResourceInUseException;
use App\Models\Option;
use App\Models\OptionGroup;
use App\Repositories\Contracts\OptionGroupRepositoryInterface;
use App\Repositories\Contracts\OptionRepositoryInterface;
use App\Support\OptionGroupRules;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CRUD option group dan option oleh Owner. Aturan konsistensi (min/max/required, default, nama unik)
 * dijaga di sini; CHECK constraint database menjadi pengaman terakhir.
 */
final class CustomizationService
{
    public function __construct(
        private readonly OptionGroupRepositoryInterface $groups,
        private readonly OptionRepositoryInterface $options,
    ) {
    }

    /** @return Collection<int, OptionGroup> */
    public function listGroups(): Collection
    {
        return $this->groups->allForAdmin();
    }

    public function group(int $id): OptionGroup
    {
        return $this->groups->findForAdminOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function createGroup(array $data): OptionGroup
    {
        $required = (bool) ($data['is_required'] ?? false);

        $attributes = [
            'name' => trim((string) $data['name']),
            'is_required' => $required,
            // Group wajib tanpa min eksplisit otomatis minimal 1.
            'min_selection' => (int) ($data['min_selection'] ?? ($required ? 1 : 0)),
            'max_selection' => (int) ($data['max_selection'] ?? 1),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];
        $this->assertSelectionRules($attributes);

        $group = $this->groups->create($attributes);

        return $this->groups->findForAdminOrFail($group->id);
    }

    /** PUT: field opsional yang tidak dikirim tidak diubah. @param array<string, mixed> $data */
    public function updateGroup(int $id, array $data): OptionGroup
    {
        $group = $this->groups->findOrFail($id);

        $attributes = [
            'name' => trim((string) $data['name']),
            'is_required' => (bool) $this->pick($data, 'is_required', $group->is_required),
            'min_selection' => (int) $this->pick($data, 'min_selection', $group->min_selection),
            'max_selection' => (int) $this->pick($data, 'max_selection', $group->max_selection),
            'sort_order' => (int) $this->pick($data, 'sort_order', $group->sort_order),
            'is_active' => (bool) $this->pick($data, 'is_active', $group->is_active),
        ];
        $this->assertSelectionRules($attributes);

        $defaults = $this->options->countDefaults($group->id, null);
        if ($defaults > $attributes['max_selection']) {
            throw ValidationException::withMessages([
                'max_selection' => ["Group memiliki {$defaults} opsi default, melebihi batas pilihan maksimal."],
            ]);
        }

        $this->groups->update($group, $attributes);

        return $this->groups->findForAdminOrFail($id);
    }

    public function deleteGroup(int $id): void
    {
        $group = $this->groups->findOrFail($id);

        $used = $this->groups->countProducts($group);
        if ($used > 0) {
            throw new ResourceInUseException(
                "Group masih dipakai oleh {$used} produk. Lepaskan dari produk atau nonaktifkan group."
            );
        }

        $this->groups->deleteWithOptions($group);
    }

    /** @param array<string, mixed> $data */
    public function createOption(array $data): Option
    {
        return DB::transaction(function () use ($data) {
            $group = $this->groups->findOrFail((int) $data['option_group_id']);
            $name = trim((string) $data['name']);
            $isDefault = (bool) ($data['is_default'] ?? false);
            $isAvailable = (bool) ($data['is_available'] ?? true);

            $this->assertNameFree($group->id, $name, null);
            $this->assertDefaultAllowed($isDefault, $isAvailable);
            $this->applyDefault($group, null, $isDefault);

            return $this->options->create([
                'option_group_id' => $group->id,
                'name' => $name,
                'price' => (int) ($data['price'] ?? 0),
                'is_available' => $isAvailable,
                'is_default' => $isDefault,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
            ]);
        });
    }

    /** option_group_id tidak dapat dipindah lewat update. @param array<string, mixed> $data */
    public function updateOption(int $id, array $data): Option
    {
        return DB::transaction(function () use ($id, $data) {
            $option = $this->options->findOrFail($id);
            $group = $this->groups->findOrFail($option->option_group_id);

            $name = trim((string) $data['name']);
            $isDefault = (bool) $this->pick($data, 'is_default', $option->is_default);
            $isAvailable = (bool) $this->pick($data, 'is_available', $option->is_available);

            $this->assertNameFree($group->id, $name, $option->id);
            $this->assertDefaultAllowed($isDefault, $isAvailable);
            $this->applyDefault($group, $option->id, $isDefault);

            return $this->options->update($option, [
                'name' => $name,
                'price' => (int) $this->pick($data, 'price', $option->price),
                'is_available' => $isAvailable,
                'is_default' => $isDefault,
                'sort_order' => (int) $this->pick($data, 'sort_order', $option->sort_order),
            ]);
        });
    }

    public function deleteOption(int $id): void
    {
        $this->options->delete($this->options->findOrFail($id));
    }

    /** @param array<string, mixed> $attributes */
    private function assertSelectionRules(array $attributes): void
    {
        $errors = OptionGroupRules::violations(
            $attributes['min_selection'], $attributes['max_selection'], $attributes['is_required'],
        );

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function assertNameFree(int $groupId, string $name, ?int $exceptId): void
    {
        if ($this->options->nameExistsInGroup($groupId, $name, $exceptId)) {
            throw ValidationException::withMessages(['name' => ['Nama opsi sudah dipakai di group ini.']]);
        }
    }

    private function assertDefaultAllowed(bool $isDefault, bool $isAvailable): void
    {
        if ($isDefault && ! $isAvailable) {
            throw ValidationException::withMessages(['is_default' => ['Opsi default harus tersedia.']]);
        }
    }

    /**
     * Group pilih-satu: menjadikan opsi ini default otomatis melepas default lain.
     * Group pilih-banyak: jumlah default tidak boleh melebihi batas maksimal.
     */
    private function applyDefault(OptionGroup $group, ?int $exceptOptionId, bool $isDefault): void
    {
        if (! $isDefault) {
            return;
        }

        if ($group->max_selection === 1) {
            $this->options->clearDefaults($group->id, $exceptOptionId);

            return;
        }

        if ($this->options->countDefaults($group->id, $exceptOptionId) + 1 > $group->max_selection) {
            throw ValidationException::withMessages([
                'is_default' => ["Jumlah opsi default melebihi batas pilihan group ({$group->max_selection})."],
            ]);
        }
    }

    /** @param array<string, mixed> $data */
    private function pick(array $data, string $key, mixed $current): mixed
    {
        return array_key_exists($key, $data) && $data[$key] !== null ? $data[$key] : $current;
    }
}
