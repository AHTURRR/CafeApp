<?php
namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CancelOrderRequest;
use App\Http\Requests\Customer\PreviewOrderRequest;
use App\Http\Requests\Customer\StoreOrderRequest;
use App\Http\Resources\Customer\CustomerOrderResource;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\Order\OrderService;
use App\Services\Order\OrderStatusService;
use App\Services\Order\PricingService;
use Illuminate\Http\Request;

class CustomerOrderController extends Controller
{
    private OrderService $orderService;
    private PricingService $pricingService;
    private OrderRepositoryInterface $orderRepository;
    private OrderStatusService $orderStatusService;

    public function __construct(
        OrderService $orderService,
        PricingService $pricingService,
        OrderRepositoryInterface $orderRepository,
        OrderStatusService $orderStatusService
    ) {
        $this->orderService = $orderService;
        $this->pricingService = $pricingService;
        $this->orderRepository = $orderRepository;
        $this->orderStatusService = $orderStatusService;
    }

    public function preview(PreviewOrderRequest $request)
    {
        // For preview, we don't require idempotency.
        // We use mocked settings for tax and service charge.
        $result = $this->pricingService->calculate($request->validated()['items'], 10, 5, 0);
        return response()->json(['data' => $result]);
    }

    public function store(StoreOrderRequest $request)
    {
        $idempotencyKey = $request->header('Idempotency-Key');
        if (!$idempotencyKey) {
            return response()->json([
                'message' => 'Header Idempotency-Key diperlukan.',
                'code' => 'VALIDATION_ERROR',
            ], 422);
        }

        $order = $this->orderService->createCustomerOrder($request->user()->id, $request->validated(), $idempotencyKey);

        $response = response()->json(['data' => new CustomerOrderResource($order)], 201);
        
        if ($order->is_idempotent_replay) {
            $response->setStatusCode(200);
            $response->header('Idempotent-Replayed', 'true');
        }

        return $response;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['status', 'active', 'updated_since']);
        $perPage = $request->query('per_page', 20);
        
        $orders = $this->orderRepository->getCustomerOrders($request->user()->id, $filters, $perPage);
        
        return CustomerOrderResource::collection($orders);
    }

    public function show(Request $request, $id)
    {
        $order = $this->orderRepository->findByIdAndUser($id, $request->user()->id);
        
        if (!$order) {
            return response()->json(['message' => 'Order tidak ditemukan', 'code' => 'NOT_FOUND'], 404);
        }

        return new CustomerOrderResource($order);
    }

    public function cancel(CancelOrderRequest $request, $id)
    {
        $order = $this->orderRepository->findByIdAndUser($id, $request->user()->id);
        
        if (!$order) {
            return response()->json(['message' => 'Order tidak ditemukan', 'code' => 'NOT_FOUND'], 404);
        }

        if ($order->status !== OrderStatus::PENDING_PAYMENT) {
            return response()->json([
                'message' => 'Hanya dapat membatalkan order yang belum dibayar.',
                'code' => 'INVALID_STATUS_TRANSITION',
                'current_status' => $order->status->value ?? $order->status,
            ], 409);
        }

        $this->orderStatusService->transition($order, OrderStatus::CANCELLED, $request->user()->id, $request->validated('reason'));

        return new CustomerOrderResource($order);
    }
}
