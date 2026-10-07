<?php

namespace Tests\Feature\Catalog;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Option;
use App\Models\OptionGroup;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\ActsAsRole;
use Tests\TestCase;

class CatalogReadTest extends TestCase
{
    use ActsAsRole, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsRole(Role::Customer);
    }

    public function test_categories_list_only_active_ones_in_order(): void
    {
        Category::factory()->create(['name' => 'Food', 'sort_order' => 2]);
        Category::factory()->create(['name' => 'Coffee', 'sort_order' => 1]);
        Category::factory()->inactive()->create(['name' => 'Tersembunyi']);

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Coffee')
            ->assertJsonPath('data.1.name', 'Food')
            ->assertJsonMissingPath('data.0.is_active');
    }

    public function test_product_list_hides_unavailable_and_inactive_category_but_shows_sold_out(): void
    {
        $category = Category::factory()->create();
        Product::factory()->for($category)->create(['name' => 'Tersedia']);
        Product::factory()->for($category)->soldOut()->create(['name' => 'Habis']);
        Product::factory()->for($category)->unavailable()->create(['name' => 'Disembunyikan']);
        Product::factory()->for(Category::factory()->inactive())->create(['name' => 'Kategori Mati']);
        Product::factory()->for($category)->create(['name' => 'Dihapus'])->delete();

        $response = $this->getJson('/api/v1/products')->assertOk();

        $this->assertSame(['Habis', 'Tersedia'], collect($response->json('data'))->pluck('name')->all());
        $response->assertJsonPath('data.0.status', 'SOLD_OUT')
            ->assertJsonPath('data.1.status', 'AVAILABLE')
            ->assertJsonStructure(['data' => [['id', 'name', 'description', 'price', 'image_url', 'status', 'category' => ['id', 'name']]], 'meta']);
    }

    public function test_status_filter_only_accepts_visible_statuses(): void
    {
        $this->getJson('/api/v1/products?status=SOLD_OUT')->assertOk();
        $this->getJson('/api/v1/products?status=UNAVAILABLE')->assertStatus(422)->assertJsonValidationErrors('status');
    }

    public function test_filter_by_category(): void
    {
        $coffee = Category::factory()->create();
        $food = Category::factory()->create();
        Product::factory()->for($coffee)->create(['name' => 'Latte']);
        Product::factory()->for($food)->create(['name' => 'Roti']);

        $this->getJson("/api/v1/products?category_id={$food->id}")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Roti');
    }

    public function test_search_is_case_insensitive_and_treats_percent_literally(): void
    {
        $category = Category::factory()->create();
        Product::factory()->for($category)->create(['name' => 'Caramel Latte']);
        Product::factory()->for($category)->create(['name' => 'Latte']);
        Product::factory()->for($category)->create(['name' => 'Americano']);
        Product::factory()->for($category)->create(['name' => 'Diskon 50% Special']);
        Product::factory()->for($category)->create(['name' => 'Diskon 50 Special']);

        $this->getJson('/api/v1/products?q=LATTE')->assertOk()->assertJsonCount(2, 'data');

        // '%' dari pengguna bukan wildcard: hanya produk yang benar-benar memuat "50%".
        $this->getJson('/api/v1/products?q='.urlencode('50%'))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Diskon 50% Special');
    }

    public function test_pagination_meta_and_per_page_cap(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(25)->for($category)->create();

        $this->getJson('/api/v1/products?per_page=10&page=3')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta', ['current_page' => 3, 'per_page' => 10, 'total' => 25, 'last_page' => 3]);

        $this->getJson('/api/v1/products?per_page=1000')->assertOk()->assertJsonPath('meta.per_page', 100);
        $this->getJson('/api/v1/products')->assertOk()->assertJsonPath('meta.per_page', 20);
    }

    public function test_popular_orders_by_units_sold_and_ignores_unpaid_orders(): void
    {
        $category = Category::factory()->create();
        $alpha = Product::factory()->for($category)->create(['name' => 'Alpha']);
        $bravo = Product::factory()->for($category)->create(['name' => 'Bravo']);
        $charlie = Product::factory()->for($category)->create(['name' => 'Charlie']);
        Product::factory()->for($category)->create(['name' => 'Delta']);

        $this->insertOrderWithItem($alpha->id, 1, 'PAID', 1);
        $this->insertOrderWithItem($bravo->id, 5, 'COMPLETED', 2);
        $this->insertOrderWithItem($charlie->id, 10, 'PENDING_PAYMENT', 3);   // belum dibayar: tidak dihitung

        $names = collect($this->getJson('/api/v1/products?popular=1')->assertOk()->json('data'))->pluck('name')->all();

        $this->assertSame(['Bravo', 'Alpha', 'Charlie', 'Delta'], $names);
    }

    public function test_product_detail_returns_customization_structure(): void
    {
        $product = Product::factory()->for(Category::factory()->create(['name' => 'Coffee']))->create(['name' => 'Latte', 'price' => 27000]);

        $size = OptionGroup::factory()->required()->create(['name' => 'Size']);
        Option::factory()->for($size, 'group')->create(['name' => 'Medium', 'sort_order' => 2, 'is_available' => true, 'is_default' => true]);
        Option::factory()->for($size, 'group')->create(['name' => 'Small', 'sort_order' => 1]);
        Option::factory()->for($size, 'group')->create(['name' => 'Large', 'sort_order' => 3, 'price' => 5000]);
        Option::factory()->for($size, 'group')->create(['name' => 'Dihapus', 'sort_order' => 4])->delete();

        $addons = OptionGroup::factory()->multi(3)->create(['name' => 'Add-ons']);
        Option::factory()->for($addons, 'group')->create(['name' => 'Extra Shot', 'price' => 5000]);
        Option::factory()->for($addons, 'group')->unavailable()->create(['name' => 'Vanilla', 'price' => 4000, 'sort_order' => 2]);

        $mati = OptionGroup::factory()->inactive()->create(['name' => 'Group Nonaktif']);
        Option::factory()->for($mati, 'group')->create();

        // Add-ons ditampilkan lebih dulu daripada Size (urutan pivot).
        $product->optionGroups()->attach([
            $size->id => ['sort_order' => 2],
            $addons->id => ['sort_order' => 1],
            $mati->id => ['sort_order' => 3],
        ]);

        $response = $this->getJson("/api/v1/products/{$product->id}")->assertOk();

        $response->assertJsonPath('data.name', 'Latte')
            ->assertJsonPath('data.price', 27000)
            ->assertJsonPath('data.category.name', 'Coffee')
            ->assertJsonPath('data.rating', ['average' => null, 'count' => 0])
            ->assertJsonCount(2, 'data.option_groups')
            ->assertJsonPath('data.option_groups.0.name', 'Add-ons')
            ->assertJsonPath('data.option_groups.0.max_selection', 3)
            ->assertJsonPath('data.option_groups.1.name', 'Size')
            ->assertJsonPath('data.option_groups.1.is_required', true)
            ->assertJsonPath('data.option_groups.1.min_selection', 1);

        $sizeOptions = collect($response->json('data.option_groups.1.options'));
        $this->assertSame(['Small', 'Medium', 'Large'], $sizeOptions->pluck('name')->all());   // urut sort_order, tanpa yang dihapus
        $this->assertTrue($sizeOptions->firstWhere('name', 'Medium')['is_default']);
        $this->assertSame(5000, $sizeOptions->firstWhere('name', 'Large')['price']);

        // Option yang tidak tersedia tetap dikirim agar UI dapat menampilkannya nonaktif.
        $addonOptions = collect($response->json('data.option_groups.0.options'));
        $this->assertFalse($addonOptions->firstWhere('name', 'Vanilla')['is_available']);
        $this->assertTrue($addonOptions->firstWhere('name', 'Extra Shot')['is_available']);
    }

    public function test_product_detail_is_404_for_unavailable_deleted_or_inactive_category(): void
    {
        $unavailable = Product::factory()->unavailable()->create();
        $deleted = Product::factory()->create();
        $deleted->delete();
        $inactiveCategory = Product::factory()->for(Category::factory()->inactive())->create();

        foreach ([$unavailable, $deleted, $inactiveCategory] as $product) {
            $this->getJson("/api/v1/products/{$product->id}")->assertStatus(404)->assertJsonPath('code', 'NOT_FOUND');
        }
        $this->getJson('/api/v1/products/999999')->assertStatus(404);
        $this->getJson('/api/v1/products/abc')->assertStatus(404);
    }

    public function test_public_settings_return_tax_and_service_charge(): void
    {
        DB::table('settings')->insert([
            ['key' => 'tax_percent', 'value' => '10', 'updated_at' => now()],
            ['key' => 'service_charge_percent', 'value' => '5', 'updated_at' => now()],
            ['key' => 'cafe_name', 'value' => '"Kopi Kita"', 'updated_at' => now()],
        ]);

        $this->getJson('/api/v1/settings/public')
            ->assertOk()
            ->assertExactJson(['data' => ['tax_percent' => 10, 'service_charge_percent' => 5, 'cafe_name' => 'Kopi Kita']]);
    }

    public function test_public_settings_fall_back_to_zero_when_not_configured(): void
    {
        $this->getJson('/api/v1/settings/public')
            ->assertOk()
            ->assertJsonPath('data.tax_percent', 0)
            ->assertJsonPath('data.service_charge_percent', 0);
    }

    private function insertOrderWithItem(int $productId, int $quantity, string $status, int $n): void
    {
        $userId = User::factory()->create()->id;

        $orderId = DB::table('orders')->insertGetId([
            'order_number' => "CF-TEST-$n", 'daily_number' => $n,
            'user_id' => $userId, 'created_by_id' => $userId,
            'source' => 'customer', 'order_type' => 'TAKE_AWAY', 'status' => $status,
            'subtotal' => 1000 * $quantity, 'total' => 1000 * $quantity,
        ]);

        DB::table('order_items')->insert([
            'order_id' => $orderId, 'product_id' => $productId, 'product_name' => 'x',
            'unit_price' => 1000, 'quantity' => $quantity, 'options_price' => 0, 'subtotal' => 1000 * $quantity,
        ]);
    }
}
