<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AssignDriverRequest;
use App\Http\Requests\Api\PlaceOrderRequest;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function place(PlaceOrderRequest $request): JsonResponse
    {
        $store = Store::findOrFail($request->validated('store_id'));

        $order = $this->orderService->placeOrder(
            $request->user(),
            $store,
            $request->validated('delivery_address'),
            $request->validated('items'),
            $request->validated('notes'),
        );

        $order->load('orderItems');

        return response()->json($order, 201);
    }

    public function myOrders(Request $request): JsonResponse
    {
        $orders = $request->user()
            ->orders()
            ->with('store', 'orderItems')
            ->latest()
            ->get();

        return response()->json($orders);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $order->load('store', 'customer', 'driver', 'orderItems.product');

        return response()->json($order);
    }

    public function storeOrders(Request $request): JsonResponse
    {
        $orders = Order::whereIn(
            'store_id',
            $request->user()->stores()->pluck('id')
        )
            ->with('customer', 'orderItems', 'driver')
            ->latest()
            ->get();

        return response()->json($orders);
    }

    public function driverOrders(Request $request): JsonResponse
    {
        $orders = Order::forDriver($request->user()->id)
            ->with('store', 'orderItems', 'customer')
            ->latest()
            ->get();

        return response()->json($orders);
    }

    public function accept(Request $request, Order $order): JsonResponse
    {
        $this->authorize('accept', $order);

        $order = $this->orderService->acceptOrder($order);

        return response()->json($order);
    }

    public function assignDriver(AssignDriverRequest $request, Order $order): JsonResponse
    {
        $this->authorize('assignDriver', $order);

        $driver = User::findOrFail($request->validated('driver_id'));

        $order = $this->orderService->assignDriver($order, $driver);

        return response()->json($order);
    }

    public function markPickedUp(Request $request, Order $order): JsonResponse
    {
        $this->authorize('markPickedUp', $order);

        $order = $this->orderService->markPickedUp($order);

        return response()->json($order);
    }

    public function markDelivered(Request $request, Order $order): JsonResponse
    {
        $this->authorize('markDelivered', $order);

        $order = $this->orderService->markDelivered($order);

        return response()->json($order);
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        $this->authorize('cancel', $order);

        $order = $this->orderService->cancelOrder($order);

        return response()->json($order);
    }
}
