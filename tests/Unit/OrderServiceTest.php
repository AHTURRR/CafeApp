<?php
namespace Tests\Unit;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\Order\OrderService;
use App\Services\Order\PricingService;
use Mockery;
use PHPUnit\Framework\TestCase;

class OrderServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_customer_order_returns_existing_order_if_idempotency_key_matches()
    {
        $userId = 1;
        $idempotencyKey = '123e4567-e89b-12d3-a456-426614174000';
        $requestData = [
            'order_type' => 'DINE_IN',
            'payment_method' => 'CASH',
            'items' => []
        ];

        $existingOrder = new Order();
        $existingOrder->id = 101;
        $existingOrder->idempotency_key = $idempotencyKey;

        $orderRepo = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepo->shouldReceive('checkIdempotency')
            ->with($userId, $idempotencyKey)
            ->once()
            ->andReturn($existingOrder);

        $pricingService = Mockery::mock(PricingService::class);

        $service = new OrderService($orderRepo, $pricingService);
        
        $result = $service->createCustomerOrder($userId, $requestData, $idempotencyKey);

        $this->assertSame($existingOrder, $result);
        $this->assertTrue($result->is_idempotent_replay);
    }
}
