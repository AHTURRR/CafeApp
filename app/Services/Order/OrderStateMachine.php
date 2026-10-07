<?php
namespace App\Services\Order;

use App\Enums\OrderStatus;
use InvalidArgumentException;

class OrderStateMachine
{
    private static array $transitions = [
        'PENDING_PAYMENT' => ['PAID', 'CANCELLED'],
        'PAID' => ['CONFIRMED', 'CANCELLED'],
        'CONFIRMED' => ['PREPARING', 'CANCELLED'],
        'PREPARING' => ['READY', 'CANCELLED'],
        'READY' => ['COMPLETED', 'CANCELLED'],
        'COMPLETED' => [],
        'CANCELLED' => [],
    ];

    public static function canTransition(OrderStatus $currentStatus, OrderStatus $newStatus): bool
    {
        return in_array($newStatus->value, self::$transitions[$currentStatus->value] ?? []);
    }

    public static function ensureCanTransition(OrderStatus $currentStatus, OrderStatus $newStatus): void
    {
        if (!self::canTransition($currentStatus, $newStatus)) {
            throw new InvalidArgumentException("Status order tidak dapat diubah dari {$currentStatus->value} ke {$newStatus->value}.");
        }
    }
}