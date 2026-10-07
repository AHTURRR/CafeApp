<?php
namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Services\Order\OrderStateMachine;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class OrderStateMachineTest extends TestCase
{
    public function test_can_transition_from_pending_payment()
    {
        $this->assertTrue(OrderStateMachine::canTransition(OrderStatus::PENDING_PAYMENT, OrderStatus::PAID));
        $this->assertTrue(OrderStateMachine::canTransition(OrderStatus::PENDING_PAYMENT, OrderStatus::CANCELLED));
        
        $this->assertFalse(OrderStateMachine::canTransition(OrderStatus::PENDING_PAYMENT, OrderStatus::CONFIRMED));
        $this->assertFalse(OrderStateMachine::canTransition(OrderStatus::PENDING_PAYMENT, OrderStatus::PREPARING));
        $this->assertFalse(OrderStateMachine::canTransition(OrderStatus::PENDING_PAYMENT, OrderStatus::READY));
        $this->assertFalse(OrderStateMachine::canTransition(OrderStatus::PENDING_PAYMENT, OrderStatus::COMPLETED));
    }

    public function test_can_transition_from_paid()
    {
        $this->assertTrue(OrderStateMachine::canTransition(OrderStatus::PAID, OrderStatus::CONFIRMED));
        $this->assertTrue(OrderStateMachine::canTransition(OrderStatus::PAID, OrderStatus::CANCELLED));
        
        $this->assertFalse(OrderStateMachine::canTransition(OrderStatus::PAID, OrderStatus::PENDING_PAYMENT));
        $this->assertFalse(OrderStateMachine::canTransition(OrderStatus::PAID, OrderStatus::PREPARING));
    }

    public function test_ensure_can_transition_throws_exception_on_invalid_transition()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Status order tidak dapat diubah dari READY ke PREPARING.');
        
        OrderStateMachine::ensureCanTransition(OrderStatus::READY, OrderStatus::PREPARING);
    }
}
