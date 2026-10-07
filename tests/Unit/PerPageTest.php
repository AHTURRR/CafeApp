<?php

namespace Tests\Unit;

use App\Support\PerPage;
use PHPUnit\Framework\TestCase;

class PerPageTest extends TestCase
{
    public function test_defaults_when_missing_or_invalid(): void
    {
        $this->assertSame(20, PerPage::resolve(null));
        $this->assertSame(20, PerPage::resolve('abc'));
        $this->assertSame(20, PerPage::resolve(0));
        $this->assertSame(20, PerPage::resolve(-5));
    }

    public function test_value_is_used_and_capped_at_100(): void
    {
        $this->assertSame(10, PerPage::resolve('10'));
        $this->assertSame(100, PerPage::resolve(100));
        $this->assertSame(100, PerPage::resolve(1000));
    }
}
