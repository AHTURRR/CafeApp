<?php
namespace App\Repositories\Eloquent;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function getCustomerOrders(int $userId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Order::with('items.options')
            ->where('user_id', $userId)
            ->where('source', 'customer');

        if (!empty($filters['status'])) {
            $query->whereIn('status', explode(',', $filters['status']));
        }
        
        if (!empty($filters['active']) && $filters['active'] == '1') {
            $query->whereNotIn('status', ['COMPLETED', 'CANCELLED']);
        }
        
        if (!empty($filters['updated_since'])) {
            $query->where('updated_at', '>=', $filters['updated_since']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function findByIdAndUser(int $orderId, int $userId): ?Order
    {
        return Order::with(['items.options', 'statusLogs', 'payment'])
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->where('source', 'customer')
            ->first();
    }

    public function findByIdLockForUpdate(int $orderId): ?Order
    {
        return Order::lockForUpdate()->find($orderId);
    }

    public function createOrder(array $data, array $items): Order
    {
        $order = Order::create($data);
        
        foreach ($items as $itemData) {
            $options = $itemData['options'] ?? [];
            unset($itemData['options']);
            
            $item = $order->items()->create($itemData);
            
            if (!empty($options)) {
                $item->options()->createMany($options);
            }
        }
        
        return $order;
    }

    public function update(Order $order, array $data): bool
    {
        return $order->update($data);
    }

    public function checkIdempotency(int $userId, string $idempotencyKey): ?Order
    {
        return Order::with(['items.options', 'statusLogs', 'payment'])
            ->where('created_by_id', $userId)
            ->where('idempotency_key', $idempotencyKey)
            ->first();
    }
}