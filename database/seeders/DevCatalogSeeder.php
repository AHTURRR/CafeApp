<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Option;
use App\Models\OptionGroup;
use App\Models\Product;
use Illuminate\Database\Seeder;

/** Menu contoh untuk local/testing. Aman dijalankan ulang (tidak menggandakan data). */
class DevCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            'Size' => [
                'attrs' => ['is_required' => true, 'min_selection' => 1, 'max_selection' => 1, 'sort_order' => 1],
                'options' => [['Small', 0, false], ['Medium', 0, true], ['Large', 5000, false]],
            ],
            'Sugar Level' => [
                'attrs' => ['is_required' => true, 'min_selection' => 1, 'max_selection' => 1, 'sort_order' => 2],
                'options' => [['Normal', 0, true], ['Less', 0, false], ['No Sugar', 0, false]],
            ],
            'Ice Level' => [
                'attrs' => ['is_required' => true, 'min_selection' => 1, 'max_selection' => 1, 'sort_order' => 3],
                'options' => [['Normal', 0, true], ['Less', 0, false], ['No Ice', 0, false]],
            ],
            'Milk' => [
                'attrs' => ['is_required' => false, 'min_selection' => 0, 'max_selection' => 1, 'sort_order' => 4],
                'options' => [['Fresh Milk', 0, true], ['Oat Milk', 4000, false], ['Almond Milk', 4000, false]],
            ],
            'Add-ons' => [
                'attrs' => ['is_required' => false, 'min_selection' => 0, 'max_selection' => 3, 'sort_order' => 5],
                'options' => [['Extra Shot', 5000, false], ['Vanilla', 4000, false], ['Caramel', 4000, false]],
            ],
        ];

        $groupIds = [];
        foreach ($groups as $name => $def) {
            $group = OptionGroup::firstOrCreate(['name' => $name], $def['attrs']);
            foreach ($def['options'] as $i => [$optionName, $price, $default]) {
                Option::firstOrCreate(
                    ['option_group_id' => $group->id, 'name' => $optionName],
                    ['price' => $price, 'is_default' => $default, 'is_available' => true, 'sort_order' => $i + 1],
                );
            }
            $groupIds[$name] = $group->id;
        }

        $category = fn (string $name) => Category::where('name', $name)->first()
            ?? Category::create(['name' => $name]);

        $products = [
            ['Latte', 'Coffee', 27000, 'Espresso dengan susu steamed.', ['Size', 'Sugar Level', 'Ice Level', 'Milk', 'Add-ons']],
            ['Americano', 'Coffee', 22000, 'Espresso dengan air.', ['Size', 'Sugar Level', 'Ice Level', 'Add-ons']],
            ['Matcha Latte', 'Non Coffee', 28000, 'Matcha dengan susu.', ['Size', 'Sugar Level', 'Ice Level', 'Milk']],
            ['Croissant', 'Food', 20000, 'Croissant mentega.', []],
        ];

        foreach ($products as [$name, $categoryName, $price, $description, $groupNames]) {
            $product = Product::firstOrCreate(['name' => $name], [
                'category_id' => $category($categoryName)->id,
                'price' => $price,
                'description' => $description,
                'status' => ProductStatus::Available,
            ]);

            if ($product->wasRecentlyCreated && $groupNames !== []) {
                $sync = [];
                foreach ($groupNames as $i => $groupName) {
                    $sync[$groupIds[$groupName]] = ['sort_order' => $i + 1];
                }
                $product->optionGroups()->sync($sync);
            }
        }
    }
}
