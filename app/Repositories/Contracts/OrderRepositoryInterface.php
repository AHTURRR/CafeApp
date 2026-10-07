<?php
namespace App\Repositories\Contracts;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface
{
    public function getCustomerOrders(int $userId, array $filters, int $perPage): LengthAwarePaginator;
    public function findByIdAndUser(int $orderId, int $userId): ?Order;
    public function findByIdLockForUpdate(int $orderId): ?Order;
    public function createOrder(array $data, array $items): Order;
    public function update(Order $order, array $data): bool;
    public function checkIdempotency(int $userId, string $idempotencyKey): ?Order;
}