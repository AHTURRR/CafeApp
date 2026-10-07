<?php

namespace Tests\Unit;

use App\Support\OptionGroupRules;
use PHPUnit\Framework\TestCase;

class OptionGroupRulesTest extends TestCase
{
    public function test_valid_combinations_have_no_violations(): void
    {
        $this->assertSame([], OptionGroupRules::violations(0, 1, false));
        $this->assertSame([], OptionGroupRules::violations(1, 1, true));
        $this->assertSame([], OptionGroupRules::violations(0, 3, false));
        $this->assertSame([], OptionGroupRules::violations(2, 3, true));
    }

    public function test_min_greater_than_max_is_rejected(): void
    {
        $this->assertArrayHasKey('min_selection', OptionGroupRules::violations(3, 2, false));
    }

    public function test_required_group_needs_min_of_at_least_one(): void
    {
        $this->assertArrayHasKey('min_selection', OptionGroupRules::violations(0, 1, true));
    }

    public function test_max_below_one_and_negative_min_are_rejected(): void
    {
        $this->assertArrayHasKey('max_selection', OptionGroupRules::violations(0, 0, false));
        $this->assertArrayHasKey('min_selection', OptionGroupRules::violations(-1, 1, false));
    }

    public function test_rules_mirror_database_check_constraint(): void
    {
        // CHECK: min>=0 AND max>=1 AND min<=max AND (NOT required OR min>=1)
        for ($min = -1; $min <= 4; $min++) {
            for ($max = 0; $max <= 4; $max++) {
                foreach ([false, true] as $required) {
                    $dbOk = $min >= 0 && $max >= 1 && $min <= $max && (! $required || $min >= 1);
                    $this->assertSame($dbOk, OptionGroupRules::violations($min, $max, $required) === [], "min=$min max=$max required=".(int) $required);
                }
            }
        }
    }
}
