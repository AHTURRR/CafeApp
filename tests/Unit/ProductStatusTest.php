<?php

namespace Tests\Unit;

use App\Enums\ProductStatus;
use PHPUnit\Framework\TestCase;

class ProductStatusTest extends TestCase
{
    public function test_values_match_database_check_constraint(): void
    {
        // Harus sama persis dengan CHECK (status IN (...)) pada migrasi products.
        $this->assertSame(['AVAILABLE', 'UNAVAILABLE', 'SOLD_OUT'], ProductStatus::values());
    }

    public function test_unavailable_is_never_visible_in_catalog(): void
    {
        $visible = ProductStatus::visibleInCatalog();

        $this->assertContains(ProductStatus::Available, $visible);
        $this->assertContains(ProductStatus::SoldOut, $visible);
        $this->assertNotContains(ProductStatus::Unavailable, $visible);
    }
}
