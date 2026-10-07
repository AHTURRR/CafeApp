<?php

namespace Database\Factories;

use App\Models\OptionGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OptionGroup> */
class OptionGroupFactory extends Factory
{
    protected $model = OptionGroup::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'is_required' => false,
            'min_selection' => 0,
            'max_selection' => 1,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    /** Wajib pilih satu (mis. Size). */
    public function required(): static
    {
        return $this->state(fn () => ['is_required' => true, 'min_selection' => 1, 'max_selection' => 1]);
    }

    /** Boleh pilih beberapa (mis. Add-ons). */
    public function multi(int $max = 3): static
    {
        return $this->state(fn () => ['is_required' => false, 'min_selection' => 0, 'max_selection' => $max]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
