<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\ActsAsRole;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use ActsAsRole, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAsRole(Role::Owner);
    }

    private function upload(Product $product, UploadedFile $file)
    {
        return $this->post("/api/v1/admin/products/{$product->id}/image", ['image' => $file], ['Accept' => 'application/json']);
    }

    public function test_owner_can_upload_product_image(): void
    {
        $product = Product::factory()->create();

        $response = $this->upload($product, UploadedFile::fake()->image('latte.png', 600, 600))->assertOk();

        $publicId = $product->fresh()->image_public_id;
        $this->assertNotNull($publicId);
        $this->assertStringStartsWith('products/', $publicId);
        $this->assertNotNull($response->json('data.image_url'));
        Storage::disk('public')->assertExists($publicId);
    }

    public function test_uploading_again_replaces_and_deletes_the_old_file(): void
    {
        $product = Product::factory()->create();

        $this->upload($product, UploadedFile::fake()->image('a.jpg'))->assertOk();
        $first = $product->fresh()->image_public_id;

        $this->upload($product, UploadedFile::fake()->image('b.jpg'))->assertOk();
        $second = $product->fresh()->image_public_id;

        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
    }

    public function test_owner_can_delete_the_image(): void
    {
        $product = Product::factory()->create();
        $this->upload($product, UploadedFile::fake()->image('a.jpg'))->assertOk();
        $publicId = $product->fresh()->image_public_id;

        $this->deleteJson("/api/v1/admin/products/{$product->id}/image")->assertNoContent();

        $fresh = $product->fresh();
        $this->assertNull($fresh->image_url);
        $this->assertNull($fresh->image_public_id);
        Storage::disk('public')->assertMissing($publicId);
    }

    public function test_non_image_file_is_rejected(): void
    {
        $product = Product::factory()->create();

        $this->upload($product, UploadedFile::fake()->create('dokumen.pdf', 10, 'application/pdf'))
            ->assertStatus(422)->assertJsonPath('code', 'VALIDATION_ERROR')->assertJsonValidationErrors('image');
        $this->assertNull($product->fresh()->image_public_id);
    }

    public function test_image_larger_than_limit_is_rejected(): void
    {
        $product = Product::factory()->create();

        $this->upload($product, UploadedFile::fake()->image('besar.jpg')->size(config('cafe.max_image_kb') + 500))
            ->assertStatus(422)->assertJsonValidationErrors('image');
    }

    public function test_image_is_required(): void
    {
        $product = Product::factory()->create();

        $this->postJson("/api/v1/admin/products/{$product->id}/image", [])
            ->assertStatus(422)->assertJsonValidationErrors('image');
    }

    public function test_image_url_is_exposed_in_catalog(): void
    {
        $product = Product::factory()->create();
        $this->upload($product, UploadedFile::fake()->image('a.jpg'))->assertOk();

        $this->actingAsRole(Role::Customer);
        $this->getJson("/api/v1/products/{$product->id}")
            ->assertOk()->assertJsonPath('data.image_url', $product->fresh()->image_url);
    }
}
