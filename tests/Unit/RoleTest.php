<?php

namespace Tests\Unit;

use App\Enums\Role;
use PHPUnit\Framework\TestCase;

class RoleTest extends TestCase
{
    public function test_customer_is_not_staff_and_others_are(): void
    {
        $this->assertFalse(Role::Customer->isStaff());
        $this->assertTrue(Role::Cashier->isStaff());
        $this->assertTrue(Role::Kitchen->isStaff());
        $this->assertTrue(Role::Owner->isStaff());
    }

    public function test_values_match_database_check_constraint(): void
    {
        // Harus sama persis dengan CHECK (role IN (...)) pada migrasi users.
        $this->assertSame(['customer', 'cashier', 'kitchen', 'owner'], Role::values());
    }
}
