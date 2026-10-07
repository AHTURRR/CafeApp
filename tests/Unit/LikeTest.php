<?php

namespace Tests\Unit;

use App\Support\Like;
use PHPUnit\Framework\TestCase;

class LikeTest extends TestCase
{
    public function test_wildcards_from_user_are_escaped(): void
    {
        $this->assertSame('50\\%', Like::escape('50%'));
        $this->assertSame('a\\_b', Like::escape('a_b'));
    }

    public function test_backslash_is_escaped_first_and_not_doubled(): void
    {
        $this->assertSame('a\\\\b', Like::escape('a\\b'));
        $this->assertSame('\\\\\\%', Like::escape('\\%'));   // \% -> \\ + \%
    }

    public function test_contains_wraps_with_wildcards(): void
    {
        $this->assertSame('%latte%', Like::contains('latte'));
        $this->assertSame('%50\\%%', Like::contains('50%'));
    }

    public function test_plain_text_is_unchanged(): void
    {
        $this->assertSame('Caramel Latte', Like::escape('Caramel Latte'));
    }
}
