<?php

namespace Tests\Feature\Admin;

use App\Enums\ProductStatus;
use App\Enums\Role;
use App\Models\Category;
use App\Models\OptionGroup;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ActsAsRole;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use ActsAsRole, RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsRole(Role::Owner);
        $this->category = Category::factory()->create(['name' => 'Coffee']);
    }

    /** @return array<string, mixed> */
    private function payload(array $override = []): array
    {
        return array_merge(['category_id' => $this->category->id, 'name' => 'Latte', 'price' => 27000], $override);
    }

    public function test_owner_creates_product_with_option_groups_in_given_order(): void
    {
        $size = OptionGroup::factory()->required()->create(['name' => 'Size']);
        $addons = OptionGroup::factory()->multi()->create(['name' => 'Add-ons']);

        $response = $this->postJson('/api/v1/admin/products', $this->payload([
            'description' => 'Espresso dengan susu.',
            'option_group_ids' => [$addons->id, $size->id],
        ]))->assertCreated();

        $response->assertJsonPath('data.name', 'Latte')
            ->assertJsonPath('data.price', 27000)
            ->assertJsonPath('data.status', 'AVAILABLE')
            ->assertJsonPath('data.stock', null)
            ->assertJsonPath('data.category.name', 'Coffee')
            ->assertJsonCount(2, 'data.option_groups')
            ->assertJsonPath('data.option_groups.0.id', $addons->id)
            ->assertJsonPath('data.option_groups.0.sort_order', 1)
            ->assertJsonPath('data.option_groups.1.id', $size->id)
            ->assertJsonPath('data.option_groups.1.sort_order', 2);

        $this->assertDatabaseHas('product_option_groups', ['option_group_id' => $addons->id, 'sort_order' => 1]);
    }

    public function test_create_validation(): void
    {
        $deleted = Category::factory()->create();
        $deleted->delete();
        $inactiveGroup = OptionGroup::factory()->inactive()->create();
        $group = OptionGroup::factory()->create();

        $this->postJson('/api/v1/admin/products', [])
            ->assertStatus(422)->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['category_id', 'name', 'price']);

        $this->postJson('/api/v1/admin/products', $this->payload(['price' => -1]))->assertJsonValidationErrors('price');
        $this->postJson('/api/v1/admin/products', $this->payload(['price' => 'abc']))->assertJsonValidationErrors('price');
        $this->postJson('/api/v1/admin/products', $this->payload(['stock' => -5]))->assertJsonValidationErrors('stock');
        $this->postJson('/api/v1/admin/products', $this->payload(['status' => 'HABIS']))->assertJsonValidationErrors('status');
        $this->postJson('/api/v1/admin/products', $this->payload(['category_id' => $deleted->id]))->assertJsonValidationErrors('category_id');
        $this->postJson('/api/v1/admin/products', $this->payload(['category_id' => 999999]))->assertJsonValidationErrors('category_id');
        $this->postJson('/api/v1/admin/products', $this->payload(['option_group_ids' => [$inactiveGroup->id]]))->assertJsonValidationErrors('option_group_ids.0');
        $this->postJson('/api/v1/admin/products', $this->payload(['option_group_ids' => [$group->id, $group->id]]))->assertJsonValidationErrors('option_group_ids.0');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_zero_stock_forces_sold_out(): void
    {
        $this->postJson('/api/v1/admin/products', $this->payload(['stock' => 0, 'status' => 'AVAILABLE']))
            ->assertCreated()->assertJsonPath('data.status', 'SOLD_OUT')->assertJsonPath('data.stock', 0);
    }

    public function test_update_changes_given_fields_and_keeps_the_rest(): void
    {
        $group = OptionGroup::factory()->create();
        $product = Product::factory()->for($this->category)->unavailable()->withStock(7)->create(['name' => 'Lama', 'price' => 10000, 'description' => 'Deskripsi']);
        $product->optionGroups()->attach($group->id, ['sort_order' => 1]);

        // Hanya field wajib: status, stok, deskripsi, dan kaitan group tidak berubah.
        $this->putJson("/api/v1/admin/products/{$product->id}", $this->payload(['name' => 'Baru', 'price' => 12000]))
            ->assertOk()
            ->assertJsonPath('data.name', 'Baru')
            ->assertJsonPath('data.price', 12000)
            ->assertJsonPath('data.status', 'UNAVAILABLE')
            ->assertJsonPath('data.stock', 7)
            ->assertJsonPath('data.description', 'Deskripsi')
            ->assertJsonCount(1, 'data.option_groups');
    }

    public function test_update_syncs_option_groups_only_when_sent(): void
    {
        $a = OptionGroup::factory()->create();
        $b = OptionGroup::factory()->create();
        $product = Product::factory()->for($this->category)->create();
        $product->optionGroups()->attach($a->id, ['sort_order' => 1]);

        $this->putJson("/api/v1/admin/products/{$product->id}", $this->payload(['option_group_ids' => [$b->id]]))
            ->assertOk()->assertJsonCount(1, 'data.option_groups')->assertJsonPath('data.option_groups.0.id', $b->id);

        $this->putJson("/api/v1/admin/products/{$product->id}", $this->payload(['option_group_ids' => []]))
            ->assertOk()->assertJsonCount(0, 'data.option_groups');
    }

    public function test_setting_stock_to_zero_on_update_marks_sold_out(): void
    {
        $product = Product::factory()->for($this->category)->withStock(5)->create();

        $this->putJson("/api/v1/admin/products/{$product->id}", $this->payload(['stock' => 0]))
            ->assertOk()->assertJsonPath('data.status', 'SOLD_OUT');
    }

    public function test_status_can_be_changed_but_not_to_available_when_stock_is_zero(): void
    {
        $product = Product::factory()->for($this->category)->create();

        $this->patchJson("/api/v1/admin/products/{$product->id}/status", ['status' => 'SOLD_OUT'])
            ->assertOk()->assertJsonPath('data.status', 'SOLD_OUT');
        $this->patchJson("/api/v1/admin/products/{$product->id}/status", ['status' => 'AVAILABLE'])
            ->assertOk()->assertJsonPath('data.status', 'AVAILABLE');
        $this->patchJson("/api/v1/admin/products/{$product->id}/status", ['status' => 'RUSAK'])
            ->assertStatus(422)->assertJsonValidationErrors('status');

        $empty = Product::factory()->for($this->category)->withStock(0)->soldOut()->create();
        $this->patchJson("/api/v1/admin/products/{$empty->id}/status", ['status' => 'AVAILABLE'])
            ->assertStatus(422)->assertJsonValidationErrors('status');
    }

    public function test_delete_is_soft_and_removes_product_from_catalog_and_admin_show(): void
    {
        $product = Product::factory()->for($this->category)->create();

        $this->deleteJson("/api/v1/admin/products/{$product->id}")->assertNoContent();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->getJson("/api/v1/admin/products/{$product->id}")->assertStatus(404);

        $this->actingAsRole(Role::Customer);
        $this->getJson("/api/v1/products/{$product->id}")->assertStatus(404);
    }

    public function test_admin_list_shows_every_status_with_filters_and_sorting(): void
    {
        $other = Category::factory()->create();
        Product::factory()->for($this->category)->create(['name' => 'Latte', 'price' => 30000]);
        Product::factory()->for($this->category)->soldOut()->create(['name' => 'Mocha', 'price' => 20000]);
        Product::factory()->for($other)->unavailable()->create(['name' => 'Roti', 'price' => 10000]);

        $names = fn (string $query) => collect($this->getJson("/api/v1/admin/products$query")->assertOk()->json('data'))->pluck('name')->all();

        $this->assertCount(3, $names(''));                                       // termasuk UNAVAILABLE
        $this->assertSame(['Roti'], $names('?status=UNAVAILABLE'));
        $this->assertSame(['Latte', 'Mocha'], $names("?category_id={$this->category->id}&sort=name"));
        $this->assertSame(['Latte', 'Mocha', 'Roti'], $names('?sort=-price'));
        $this->assertSame(['Roti', 'Mocha', 'Latte'], $names('?sort=price'));
        $this->assertSame(['Mocha'], $names('?q=moc'));

        $this->getJson('/api/v1/admin/products?sort=password')->assertStatus(422)->assertJsonValidationErrors('sort');
    }

    public function test_admin_list_has_standard_pagination_meta(): void
    {
        Product::factory()->count(3)->for($this->category)->create();

        $this->getJson('/api/v1/admin/products?per_page=2')
            ->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('meta', ['current_page' => 1, 'per_page' => 2, 'total' => 3, 'last_page' => 2]);
    }

    public function test_sync_option_groups_endpoint(): void
    {
        $a = OptionGroup::factory()->create();
        $b = OptionGroup::factory()->create();
        $inactive = OptionGroup::factory()->inactive()->create();
        $product = Product::factory()->for($this->category)->create();

        $this->putJson("/api/v1/admin/products/{$product->id}/option-groups", [
            'option_groups' => [['id' => $a->id, 'sort_order' => 5], ['id' => $b->id]],
        ])->assertOk()
            ->assertJsonPath('data.option_groups.0.id', $b->id)        // tanpa sort_order = urutan kirim (2)
            ->assertJsonPath('data.option_groups.0.sort_order', 2)
            ->assertJsonPath('data.option_groups.1.id', $a->id)
            ->assertJsonPath('data.option_groups.1.sort_order', 5);

        $this->putJson("/api/v1/admin/products/{$product->id}/option-groups", ['option_groups' => []])
            ->assertOk()->assertJsonCount(0, 'data.option_groups');

        $this->putJson("/api/v1/admin/products/{$product->id}/option-groups", ['option_groups' => [['id' => $inactive->id]]])
            ->assertStatus(422)->assertJsonValidationErrors('option_groups.0.id');
        $this->putJson("/api/v1/admin/products/{$product->id}/option-groups", ['option_groups' => [['id' => $a->id], ['id' => $a->id]]])
            ->assertStatus(422);
        $this->putJson("/api/v1/admin/products/{$product->id}/option-groups", [])
            ->assertStatus(422)->assertJsonValidationErrors('option_groups');
    }

    public function test_unknown_product_returns_404(): void
    {
        $this->getJson('/api/v1/admin/products/999999')->assertStatus(404)->assertJsonPath('code', 'NOT_FOUND');
        $this->deleteJson('/api/v1/admin/products/999999')->assertStatus(404);
        $this->patchJson('/api/v1/admin/products/999999/status', ['status' => 'AVAILABLE'])->assertStatus(404);
    }

    public function test_status_enum_values_are_accepted(): void
    {
        foreach (ProductStatus::cases() as $status) {
            $this->postJson('/api/v1/admin/products', $this->payload(['name' => 'P '.$status->value, 'status' => $status->value]))
                ->assertCreated()->assertJsonPath('data.status', $status->value);
        }
    }
}
