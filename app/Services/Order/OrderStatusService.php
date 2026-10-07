<?php
namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderStatusService
{
    public function transition(Order $order, OrderStatus $newStatus, ?int $changedById = null, ?string $reason = null): Order
    {
        OrderStateMachine::ensureCanTransition($order->status, $newStatus);

        DB::transaction(function () use ($order, $newStatus, $changedById, $reason) {
            $fromStatus = $order->status;
            
            $updates = ['status' => $newStatus];
            
            $timestampField = strtolower($newStatus->value) . '_at';
            if ($newStatus === OrderStatus::PENDING_PAYMENT || $newStatus === OrderStatus::PAID) {
                // No specific timestamp for PENDING_PAYMENT or PAID in orders table directly (payments has paid_at)
            } elseif (in_array($timestampField, ['confirmed_at', 'preparing_at', 'ready_at', 'completed_at', 'cancelled_at'])) {
                $updates[$timestampField] = now();
            }
            
            if ($newStatus === OrderStatus::CANCELLED) {
                $updates['cancelled_by_id'] = $changedById;
                $updates['cancel_reason'] = $reason;
            }
            
            $order->update($updates);
            
            $order->statusLogs()->create([
                'from_status' => $fromStatus,
                'to_status' => $newStatus,
                'changed_by_id' => $changedById,
                'reason' => $reason,
            ]);
            
            // Dispatch events if necessary (P1)
        });

        return $order->refresh();
    }
}