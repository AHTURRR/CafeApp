<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Option;
use App\Models\OptionGroup;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ActsAsRole;
use Tests\TestCase;

class CustomizationManagementTest extends TestCase
{
    use ActsAsRole, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsRole(Role::Owner);
    }

    // ------------------------------------------------------------------ option group

    public function test_owner_creates_group(): void
    {
        $this->postJson('/api/v1/admin/customization/groups', [
            'name' => 'Add-ons', 'min_selection' => 0, 'max_selection' => 3, 'sort_order' => 5,
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Add-ons')
            ->assertJsonPath('data.is_required', false)
            ->assertJsonPath('data.max_selection', 3)
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.options', []);
    }

    public function test_required_group_without_explicit_min_gets_min_one(): void
    {
        $this->postJson('/api/v1/admin/customization/groups', ['name' => 'Size', 'is_required' => true])
            ->assertCreated()->assertJsonPath('data.min_selection', 1)->assertJsonPath('data.max_selection', 1);
    }

    public function test_group_selection_rules_are_validated(): void
    {
        $post = fn (array $d) => $this->postJson('/api/v1/admin/customization/groups', ['name' => 'G'] + $d);

        $post(['min_selection' => 3, 'max_selection' => 2])->assertStatus(422)->assertJsonValidationErrors('min_selection');
        $post(['is_required' => true, 'min_selection' => 0])->assertStatus(422)->assertJsonValidationErrors('min_selection');
        $post(['max_selection' => 0])->assertStatus(422)->assertJsonValidationErrors('max_selection');
        $post(['max_selection' => 99])->assertStatus(422)->assertJsonValidationErrors('max_selection');
        $this->postJson('/api/v1/admin/customization/groups', [])->assertStatus(422)->assertJsonValidationErrors('name');

        $this->assertDatabaseCount('option_groups', 0);   // tidak ada yang lolos ke database
    }

    public function test_update_group_keeps_unsent_fields_and_revalidates_merged_values(): void
    {
        $group = OptionGroup::factory()->create(['name' => 'Size', 'min_selection' => 0, 'max_selection' => 3, 'sort_order' => 4]);

        $this->putJson("/api/v1/admin/customization/groups/{$group->id}", ['name' => 'Ukuran'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Ukuran')
            ->assertJsonPath('data.max_selection', 3)
            ->assertJsonPath('data.sort_order', 4);

        // is_required=true dengan min yang masih 0 (dari data lama) tidak valid.
        $this->putJson("/api/v1/admin/customization/groups/{$group->id}", ['name' => 'Ukuran', 'is_required' => true])
            ->assertStatus(422)->assertJsonValidationErrors('min_selection');

        $this->putJson("/api/v1/admin/customization/groups/{$group->id}", ['name' => 'Ukuran', 'is_required' => true, 'min_selection' => 1])
            ->assertOk()->assertJsonPath('data.is_required', true);
    }

    public function test_cannot_lower_max_selection_below_number_of_default_options(): void
    {
        $group = OptionGroup::factory()->multi(3)->create();
        Option::factory()->for($group, 'group')->asDefault()->count(2)->create();

        $this->putJson("/api/v1/admin/customization/groups/{$group->id}", ['name' => 'X', 'max_selection' => 1])
            ->assertStatus(422)->assertJsonValidationErrors('max_selection');
    }

    public function test_group_list_and_show_include_options_and_product_counts(): void
    {
        $group = OptionGroup::factory()->create(['name' => 'Size']);
        Option::factory()->for($group, 'group')->create(['name' => 'Small', 'sort_order' => 1]);
        Option::factory()->for($group, 'group')->create(['name' => 'Large', 'sort_order' => 2]);
        Option::factory()->for($group, 'group')->create(['name' => 'Dihapus'])->delete();
        Product::factory()->count(2)->create()->each(fn ($p) => $p->optionGroups()->attach($group->id));

        $this->getJson('/api/v1/admin/customization/groups')
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.products_count', 2)
            ->assertJsonCount(2, 'data.0.options');

        $this->getJson("/api/v1/admin/customization/groups/{$group->id}")
            ->assertOk()->assertJsonPath('data.options.0.name', 'Small')->assertJsonPath('data.options.1.name', 'Large');
    }

    public function test_group_in_use_by_a_product_cannot_be_deleted(): void
    {
        $group = OptionGroup::factory()->create();
        Product::factory()->create()->optionGroups()->attach($group->id);

        $this->deleteJson("/api/v1/admin/customization/groups/{$group->id}")
            ->assertStatus(409)->assertJsonPath('code', 'RESOURCE_IN_USE');

        $this->assertNotSoftDeleted('option_groups', ['id' => $group->id]);
    }

    public function test_group_used_only_by_deleted_products_can_be_deleted(): void
    {
        $group = OptionGroup::factory()->create();
        $product = Product::factory()->create();
        $product->optionGroups()->attach($group->id);
        $product->delete();

        $this->deleteJson("/api/v1/admin/customization/groups/{$group->id}")->assertNoContent();
    }

    public function test_deleting_unused_group_soft_deletes_its_options_too(): void
    {
        $group = OptionGroup::factory()->create();
        $option = Option::factory()->for($group, 'group')->create();

        $this->deleteJson("/api/v1/admin/customization/groups/{$group->id}")->assertNoContent();

        $this->assertSoftDeleted('option_groups', ['id' => $group->id]);
        $this->assertSoftDeleted('options', ['id' => $option->id]);
        $this->getJson("/api/v1/admin/customization/groups/{$group->id}")->assertStatus(404);
    }

    // ------------------------------------------------------------------ option

    public function test_owner_creates_option_with_defaults(): void
    {
        $group = OptionGroup::factory()->multi()->create();

        $this->postJson('/api/v1/admin/customization/options', [
            'option_group_id' => $group->id, 'name' => 'Extra Shot', 'price' => 5000,
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Extra Shot')
            ->assertJsonPath('data.price', 5000)
            ->assertJsonPath('data.is_available', true)
            ->assertJsonPath('data.is_default', false)
            ->assertJsonPath('data.option_group_id', $group->id);
    }

    public function test_option_validation(): void
    {
        $group = OptionGroup::factory()->create();
        $deleted = OptionGroup::factory()->create();
        $deleted->delete();

        $post = fn (array $d) => $this->postJson('/api/v1/admin/customization/options', $d);

        $post([])->assertStatus(422)->assertJsonValidationErrors(['option_group_id', 'name']);
        $post(['option_group_id' => $group->id, 'name' => 'X', 'price' => -1])->assertJsonValidationErrors('price');
        $post(['option_group_id' => 999999, 'name' => 'X'])->assertJsonValidationErrors('option_group_id');
        $post(['option_group_id' => $deleted->id, 'name' => 'X'])->assertJsonValidationErrors('option_group_id');
    }

    public function test_option_name_must_be_unique_within_group_only(): void
    {
        $a = OptionGroup::factory()->create();
        $b = OptionGroup::factory()->create();
        Option::factory()->for($a, 'group')->create(['name' => 'Normal']);

        $this->postJson('/api/v1/admin/customization/options', ['option_group_id' => $a->id, 'name' => 'normal'])
            ->assertStatus(422)->assertJsonValidationErrors('name');
        $this->postJson('/api/v1/admin/customization/options', ['option_group_id' => $b->id, 'name' => 'Normal'])
            ->assertCreated();
    }

    public function test_making_an_option_default_in_single_select_group_replaces_previous_default(): void
    {
        $group = OptionGroup::factory()->required()->create();
        $first = Option::factory()->for($group, 'group')->asDefault()->create(['name' => 'Medium']);

        $this->postJson('/api/v1/admin/customization/options', [
            'option_group_id' => $group->id, 'name' => 'Large', 'is_default' => true,
        ])->assertCreated()->assertJsonPath('data.is_default', true);

        $this->assertFalse($first->fresh()->is_default);
        $this->assertSame(1, Option::where('option_group_id', $group->id)->where('is_default', true)->count());
    }

    public function test_default_options_in_multi_select_group_cannot_exceed_max(): void
    {
        $group = OptionGroup::factory()->multi(2)->create();
        $post = fn (string $name) => $this->postJson('/api/v1/admin/customization/options', [
            'option_group_id' => $group->id, 'name' => $name, 'is_default' => true,
        ]);

        $post('A')->assertCreated();
        $post('B')->assertCreated();
        $post('C')->assertStatus(422)->assertJsonValidationErrors('is_default');
    }

    public function test_default_option_must_be_available(): void
    {
        $group = OptionGroup::factory()->create();

        $this->postJson('/api/v1/admin/customization/options', [
            'option_group_id' => $group->id, 'name' => 'X', 'is_default' => true, 'is_available' => false,
        ])->assertStatus(422)->assertJsonValidationErrors('is_default');
    }

    public function test_update_option_keeps_unsent_fields_and_ignores_group_change(): void
    {
        $group = OptionGroup::factory()->create();
        $other = OptionGroup::factory()->create();
        $option = Option::factory()->for($group, 'group')->create(['name' => 'Oat', 'price' => 4000, 'sort_order' => 3]);

        $this->putJson("/api/v1/admin/customization/options/{$option->id}", [
            'name' => 'Oat Milk', 'option_group_id' => $other->id,
        ])->assertOk()
            ->assertJsonPath('data.name', 'Oat Milk')
            ->assertJsonPath('data.price', 4000)
            ->assertJsonPath('data.sort_order', 3)
            ->assertJsonPath('data.option_group_id', $group->id);
    }

    public function test_cannot_make_a_default_option_unavailable_without_clearing_default(): void
    {
        $group = OptionGroup::factory()->create();
        $option = Option::factory()->for($group, 'group')->asDefault()->create(['name' => 'Medium']);

        $this->putJson("/api/v1/admin/customization/options/{$option->id}", ['name' => 'Medium', 'is_available' => false])
            ->assertStatus(422)->assertJsonValidationErrors('is_default');

        $this->putJson("/api/v1/admin/customization/options/{$option->id}", ['name' => 'Medium', 'is_available' => false, 'is_default' => false])
            ->assertOk()->assertJsonPath('data.is_available', false);
    }

    public function test_option_can_keep_its_own_name_on_update(): void
    {
        $group = OptionGroup::factory()->create();
        $option = Option::factory()->for($group, 'group')->create(['name' => 'Normal']);

        $this->putJson("/api/v1/admin/customization/options/{$option->id}", ['name' => 'Normal', 'price' => 1000])
            ->assertOk()->assertJsonPath('data.price', 1000);
    }

    public function test_deleting_option_is_soft_and_hides_it_from_the_catalog(): void
    {
        $group = OptionGroup::factory()->create();
        $keep = Option::factory()->for($group, 'group')->create(['name' => 'Keep']);
        $remove = Option::factory()->for($group, 'group')->create(['name' => 'Remove']);
        $product = Product::factory()->create();
        $product->optionGroups()->attach($group->id);

        $this->deleteJson("/api/v1/admin/customization/options/{$remove->id}")->assertNoContent();
        $this->assertSoftDeleted('options', ['id' => $remove->id]);

        $this->actingAsRole(Role::Customer);
        $names = collect($this->getJson("/api/v1/products/{$product->id}")->json('data.option_groups.0.options'))->pluck('name')->all();
        $this->assertSame([$keep->name], $names);
    }

    public function test_unknown_ids_return_404(): void
    {
        $this->putJson('/api/v1/admin/customization/groups/999999', ['name' => 'X'])->assertStatus(404);
        $this->putJson('/api/v1/admin/customization/options/999999', ['name' => 'X'])->assertStatus(404);
        $this->deleteJson('/api/v1/admin/customization/options/999999')->assertStatus(404);
    }
}
