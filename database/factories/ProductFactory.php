<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'price' => fake()->randomElement([15000, 22000, 27000, 30000]),
            'stock' => null,
            'status' => ProductStatus::Available,
        ];
    }

    public function soldOut(): static
    {
        return $this->state(fn () => ['status' => ProductStatus::SoldOut]);
    }

    public function unavailable(): static
    {
        return $this->state(fn () => ['status' => ProductStatus::Unavailable]);
    }

    public function withStock(int $stock): static
    {
        return $this->state(fn () => ['stock' => $stock]);
    }
}
