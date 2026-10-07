<?php
namespace Tests\Unit;

use App\Enums\ProductStatus;
use App\Models\Option;
use App\Models\OptionGroup;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\Order\PricingService;
use PHPUnit\Framework\TestCase;
use Mockery;
use Illuminate\Database\Eloquent\Collection;

class PricingServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_calculate_with_valid_items()
    {
        $product = new Product();
        $product->id = 1;
        $product->name = 'Latte';
        $product->price = 20000;
        $product->status = ProductStatus::Available;

        $repo = Mockery::mock(ProductRepositoryInterface::class);
        $repo->shouldReceive('findById')->with(1)->andReturn($product);

        $product->setRelation('optionGroups', new Collection());

        $service = new PricingService($repo);

        $items = [
            [
                'product_id' => 1,
                'quantity' => 2,
            ]
        ];

        $result = $service->calculate($items, 10, 5, 0);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['issues']);
        $this->assertEquals(40000, $result['subtotal']); // 20000 * 2
        $this->assertEquals(4000, $result['tax_amount']); // 10% of 40000
        $this->assertEquals(2000, $result['service_charge_amount']); // 5% of 40000
        $this->assertEquals(46000, $result['total']); // 40000 + 4000 + 2000
    }

    public function test_calculate_with_unavailable_product()
    {
        $product = new Product();
        $product->id = 1;
        $product->name = 'Latte';
        $product->price = 20000;
        $product->status = ProductStatus::SoldOut;

        $repo = Mockery::mock(ProductRepositoryInterface::class);
        $repo->shouldReceive('findById')->with(1)->andReturn($product);

        $service = new PricingService($repo);

        $items = [
            [
                'product_id' => 1,
                'quantity' => 1,
            ]
        ];

        $result = $service->calculate($items, 10, 5, 0);

        $this->assertFalse($result['valid']);
        $this->assertCount(1, $result['issues']);
        $this->assertEquals('PRODUCT_SOLD_OUT', $result['issues'][0]['code']);
    }
}
