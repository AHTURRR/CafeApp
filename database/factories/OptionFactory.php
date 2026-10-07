<?php

namespace Database\Factories;

use App\Models\Option;
use App\Models\OptionGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Option> */
class OptionFactory extends Factory
{
    protected $model = Option::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'option_group_id' => OptionGroup::factory(),
            'name' => fake()->unique()->words(2, true),
            'price' => 0,
            'is_available' => true,
            'is_default' => false,
            'sort_order' => 0,
        ];
    }

    public function unavailable(): static
    {
        return $this->state(fn () => ['is_available' => false]);
    }

    public function asDefault(): static
    {
        return $this->state(fn () => ['is_default' => true]);
    }
}
