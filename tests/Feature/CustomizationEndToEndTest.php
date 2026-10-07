<?php

namespace Tests\Feature;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ActsAsRole;
use Tests\TestCase;

/**
 * Definition of Done I-2: Owner membuat Latte beserta customization,
 * lalu Customer melihat struktur lengkapnya di Product Detail.
 */
class CustomizationEndToEndTest extends TestCase
{
    use ActsAsRole, RefreshDatabase;

    public function test_owner_builds_latte_and_customer_sees_the_full_customization(): void
    {
        $this->actingAsRole(Role::Owner);

        $category = $this->postJson('/api/v1/admin/categories', ['name' => 'Coffee'])->assertCreated()->json('data.id');

        $size = $this->postJson('/api/v1/admin/customization/groups', ['name' => 'Size', 'is_required' => true, 'sort_order' => 1])
            ->assertCreated()->json('data.id');
        $addons = $this->postJson('/api/v1/admin/customization/groups', ['name' => 'Add-ons', 'max_selection' => 3, 'sort_order' => 2])
            ->assertCreated()->json('data.id');

        foreach ([['Small', 0, false, 1], ['Medium', 0, true, 2], ['Large', 5000, false, 3]] as [$name, $price, $default, $order]) {
            $this->postJson('/api/v1/admin/customization/options', [
                'option_group_id' => $size, 'name' => $name, 'price' => $price, 'is_default' => $default, 'sort_order' => $order,
            ])->assertCreated();
        }
        foreach ([['Extra Shot', 5000], ['Vanilla', 4000]] as $i => [$name, $price]) {
            $this->postJson('/api/v1/admin/customization/options', [
                'option_group_id' => $addons, 'name' => $name, 'price' => $price, 'sort_order' => $i + 1,
            ])->assertCreated();
        }

        $product = $this->postJson('/api/v1/admin/products', [
            'category_id' => $category, 'name' => 'Latte', 'price' => 27000,
            'description' => 'Espresso dengan susu steamed.', 'option_group_ids' => [$size, $addons],
        ])->assertCreated()->json('data.id');

        // Customer membuka Product Detail.
        $this->actingAsRole(Role::Customer);

        $detail = $this->getJson("/api/v1/products/$product")->assertOk();

        $detail->assertJsonPath('data.name', 'Latte')
            ->assertJsonPath('data.price', 27000)
            ->assertJsonPath('data.category.name', 'Coffee')
            ->assertJsonCount(2, 'data.option_groups')
            ->assertJsonPath('data.option_groups.0.name', 'Size')
            ->assertJsonPath('data.option_groups.0.is_required', true)
            ->assertJsonPath('data.option_groups.0.min_selection', 1)
            ->assertJsonPath('data.option_groups.0.max_selection', 1)
            ->assertJsonPath('data.option_groups.1.name', 'Add-ons')
            ->assertJsonPath('data.option_groups.1.max_selection', 3);

        $this->assertSame(
            ['Small' => 0, 'Medium' => 0, 'Large' => 5000],
            collect($detail->json('data.option_groups.0.options'))->pluck('price', 'name')->all(),
        );
        $this->assertSame(
            ['Medium'],
            collect($detail->json('data.option_groups.0.options'))->where('is_default', true)->pluck('name')->values()->all(),
        );
        $this->assertSame(
            ['Extra Shot' => 5000, 'Vanilla' => 4000],
            collect($detail->json('data.option_groups.1.options'))->pluck('price', 'name')->all(),
        );

        // Produk tampil di daftar menu, dan Customer tidak boleh mengubah apa pun.
        $this->getJson('/api/v1/products?q=latte')->assertOk()->assertJsonCount(1, 'data');
        $this->putJson("/api/v1/admin/products/$product", ['category_id' => $category, 'name' => 'Hack', 'price' => 1])
            ->assertStatus(403)->assertJsonPath('code', 'FORBIDDEN');
    }
}
