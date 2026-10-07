<?php

namespace Tests\Unit;

use App\Enums\ProductStatus;
use App\Support\ProductStockPolicy;
use PHPUnit\Framework\TestCase;

class ProductStockPolicyTest extends TestCase
{
    public function test_zero_stock_turns_available_into_sold_out(): void
    {
        $this->assertSame(ProductStatus::SoldOut, ProductStockPolicy::resolveStatus(ProductStatus::Available, 0));
    }

    public function test_other_combinations_keep_requested_status(): void
    {
        $this->assertSame(ProductStatus::Available, ProductStockPolicy::resolveStatus(ProductStatus::Available, 5));
        $this->assertSame(ProductStatus::Available, ProductStockPolicy::resolveStatus(ProductStatus::Available, null));
        $this->assertSame(ProductStatus::Unavailable, ProductStockPolicy::resolveStatus(ProductStatus::Unavailable, 0));
        $this->assertSame(ProductStatus::SoldOut, ProductStockPolicy::resolveStatus(ProductStatus::SoldOut, 10));
    }

    public function test_can_be_available(): void
    {
        $this->assertTrue(ProductStockPolicy::canBeAvailable(null));
        $this->assertTrue(ProductStockPolicy::canBeAvailable(3));
        $this->assertFalse(ProductStockPolicy::canBeAvailable(0));
    }
}
