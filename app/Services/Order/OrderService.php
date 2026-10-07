<?php
namespace App\Services\Order;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    private OrderRepositoryInterface $orderRepository;
    private PricingService $pricingService;

    public function __construct(OrderRepositoryInterface $orderRepository, PricingService $pricingService)
    {
        $this->orderRepository = $orderRepository;
        $this->pricingService = $pricingService;
    }

    public function createCustomerOrder(int $userId, array $requestData, string $idempotencyKey): Order
    {
        // Check idempotency
        $existingOrder = $this->orderRepository->checkIdempotency($userId, $idempotencyKey);
        if ($existingOrder) {
            $existingOrder->is_idempotent_replay = true;
            return $existingOrder;
        }

        return DB::transaction(function () use ($userId, $requestData, $idempotencyKey) {
            // Get settings (mocked for now, should come from setting service or DB)
            $taxPercent = 10;
            $servicePercent = 5;

            $pricingResult = $this->pricingService->calculate($requestData['items'], $taxPercent, $servicePercent, 0);

            if (!$pricingResult['valid']) {
                throw new \App\Exceptions\OrderValidationException('Beberapa item tidak dapat dipesan.', $pricingResult['issues']);
            }

            if (isset($requestData['expected_total']) && $requestData['expected_total'] !== $pricingResult['total']) {
                throw new \App\Exceptions\PriceChangedException('Harga telah berubah.', $pricingResult['total']);
            }

            $dateStr = now()->format('Ymd');
            // Get next daily number using DB lock or counter table
            $counter = DB::table('order_counters')
                ->where('date', $dateStr)
                ->lockForUpdate()
                ->first();

            if (!$counter) {
                DB::table('order_counters')->insert(['date' => $dateStr, 'last_number' => 1]);
                $dailyNumber = 1;
            } else {
                $dailyNumber = $counter->last_number + 1;
                DB::table('order_counters')->where('date', $dateStr)->update(['last_number' => $dailyNumber]);
            }

            $orderNumber = 'CF-' . $dateStr . '-' . str_pad($dailyNumber, 3, '0', STR_PAD_LEFT);

            $orderData = [
                'order_number' => $orderNumber,
                'daily_number' => $dailyNumber,
                'user_id' => $userId,
                'created_by_id' => $userId,
                'source' => OrderSource::CUSTOMER,
                'order_type' => OrderType::from($requestData['order_type']),
                'table_number' => $requestData['table_number'] ?? null,
                'status' => OrderStatus::PENDING_PAYMENT,
                'subtotal' => $pricingResult['subtotal'],
                'discount_amount' => $pricingResult['discount_amount'],
                'tax_percent' => $pricingResult['tax_percent'],
                'tax_amount' => $pricingResult['tax_amount'],
                'service_charge_percent' => $pricingResult['service_charge_percent'],
                'service_charge_amount' => $pricingResult['service_charge_amount'],
                'total' => $pricingResult['total'],
                'idempotency_key' => $idempotencyKey,
            ];

            $order = $this->orderRepository->createOrder($orderData, $pricingResult['items']);

            // Create initial status log
            $order->statusLogs()->create([
                'from_status' => null,
                'to_status' => OrderStatus::PENDING_PAYMENT,
                'changed_by_id' => $userId,
            ]);

            // Setup payment
            $order->payment()->create([
                'method' => PaymentMethod::from($requestData['payment_method']),
                'amount' => $pricingResult['total'],
                'status' => \App\Enums\PaymentStatus::PENDING,
            ]);

            return $order->load(['items.options', 'statusLogs', 'payment']);
        });
    }
}
