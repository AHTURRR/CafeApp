<?php

namespace Tests\Unit;

use App\DataTransferObjects\AdminProductFilter;
use App\DataTransferObjects\CatalogFilter;
use App\Enums\ProductStatus;
use PHPUnit\Framework\TestCase;

class CatalogFilterTest extends TestCase
{
    public function test_defaults(): void
    {
        $f = CatalogFilter::fromArray([]);

        $this->assertNull($f->categoryId);
        $this->assertNull($f->q);
        $this->assertNull($f->status);
        $this->assertFalse($f->popular);
        $this->assertSame(1, $f->page);
        $this->assertSame(20, $f->perPage);
    }

    public function test_values_are_converted_and_cleaned(): void
    {
        $f = CatalogFilter::fromArray([
            'category_id' => '3', 'q' => '  latte ', 'status' => 'SOLD_OUT',
            'popular' => '1', 'page' => '2', 'per_page' => '1000',
        ]);

        $this->assertSame(3, $f->categoryId);
        $this->assertSame('latte', $f->q);
        $this->assertSame(ProductStatus::SoldOut, $f->status);
        $this->assertTrue($f->popular);
        $this->assertSame(2, $f->page);
        $this->assertSame(100, $f->perPage);
    }

    public function test_blank_query_becomes_null_and_popular_false_strings_are_false(): void
    {
        $f = CatalogFilter::fromArray(['q' => '   ', 'popular' => '0']);

        $this->assertNull($f->q);
        $this->assertFalse($f->popular);
    }

    public function test_admin_filter_defaults_sort_to_newest_first(): void
    {
        $this->assertSame('-created_at', AdminProductFilter::fromArray([])->sort);
        $this->assertSame('price', AdminProductFilter::fromArray(['sort' => 'price'])->sort);
        $this->assertSame(ProductStatus::Unavailable, AdminProductFilter::fromArray(['status' => 'UNAVAILABLE'])->status);
    }
}
