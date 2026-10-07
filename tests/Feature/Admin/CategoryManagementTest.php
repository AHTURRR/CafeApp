<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ActsAsRole;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use ActsAsRole, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsRole(Role::Owner);
    }

    public function test_owner_can_create_category_with_defaults(): void
    {
        $this->postJson('/api/v1/admin/categories', ['name' => 'Coffee'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Coffee')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.sort_order', 0);

        $this->assertDatabaseHas('categories', ['name' => 'Coffee']);
    }

    public function test_name_is_required_and_unique_ignoring_case(): void
    {
        Category::factory()->create(['name' => 'Coffee']);

        $this->postJson('/api/v1/admin/categories', [])->assertStatus(422)->assertJsonValidationErrors('name');
        $this->postJson('/api/v1/admin/categories', ['name' => 'coffee'])
            ->assertStatus(422)->assertJsonPath('code', 'VALIDATION_ERROR')->assertJsonValidationErrors('name');
    }

    public function test_name_of_deleted_category_can_be_reused(): void
    {
        Category::factory()->create(['name' => 'Coffee'])->delete();

        $this->postJson('/api/v1/admin/categories', ['name' => 'Coffee'])->assertCreated();
    }

    public function test_update_allows_keeping_own_name_and_rejects_someone_elses(): void
    {
        $coffee = Category::factory()->create(['name' => 'Coffee', 'sort_order' => 3]);
        Category::factory()->create(['name' => 'Food']);

        $this->putJson("/api/v1/admin/categories/{$coffee->id}", ['name' => 'Coffee', 'description' => 'Minuman kopi'])
            ->assertOk()
            ->assertJsonPath('data.description', 'Minuman kopi')
            ->assertJsonPath('data.sort_order', 3);   // tidak dikirim = tidak berubah

        $this->putJson("/api/v1/admin/categories/{$coffee->id}", ['name' => 'FOOD'])
            ->assertStatus(422)->assertJsonValidationErrors('name');
    }

    public function test_admin_list_includes_inactive_categories_with_product_counts(): void
    {
        $active = Category::factory()->create(['name' => 'A', 'sort_order' => 1]);
        Category::factory()->inactive()->create(['name' => 'B', 'sort_order' => 2]);
        Product::factory()->count(2)->for($active)->create();
        Product::factory()->for($active)->create()->delete();   // tidak dihitung

        $response = $this->getJson('/api/v1/admin/categories')->assertOk()->assertJsonCount(2, 'data');

        $response->assertJsonPath('data.0.products_count', 2)
            ->assertJsonPath('data.1.is_active', false)
            ->assertJsonPath('data.1.products_count', 0);
    }

    public function test_empty_category_can_be_deleted_softly(): void
    {
        $category = Category::factory()->create();

        $this->deleteJson("/api/v1/admin/categories/{$category->id}")->assertNoContent();

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $category = Category::factory()->create();
        Product::factory()->for($category)->create();

        $this->deleteJson("/api/v1/admin/categories/{$category->id}")
            ->assertStatus(409)->assertJsonPath('code', 'RESOURCE_IN_USE');

        $this->assertNotSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_category_with_only_deleted_products_can_be_deleted(): void
    {
        $category = Category::factory()->create();
        Product::factory()->for($category)->create()->delete();

        $this->deleteJson("/api/v1/admin/categories/{$category->id}")->assertNoContent();
    }

    public function test_deactivating_a_category_hides_it_and_its_products_from_the_catalog(): void
    {
        $category = Category::factory()->create(['name' => 'Coffee']);
        Product::factory()->for($category)->create(['name' => 'Latte']);

        $this->putJson("/api/v1/admin/categories/{$category->id}", ['name' => 'Coffee', 'is_active' => false])
            ->assertOk()->assertJsonPath('data.is_active', false);

        $this->actingAsRole(Role::Customer);
        $this->getJson('/api/v1/categories')->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/products')->assertJsonCount(0, 'data');
    }

    public function test_unknown_category_returns_404(): void
    {
        $this->putJson('/api/v1/admin/categories/999999', ['name' => 'X'])->assertStatus(404);
        $this->deleteJson('/api/v1/admin/categories/999999')->assertStatus(404);
    }
}
