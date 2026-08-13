<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function index(Request $request)
    {
        $orders = $request->user()->isAdmin()
            ? Order::with('items')->latest()->paginate(15)
            : $request->user()->orders()->with('items')->latest()->paginate(15);

        return OrderResource::collection($orders);
    }

    public function show(Request $request, Order $order)
    {
        $this->authorize('view', $order);

        return new OrderResource($order->load(['items', 'payment']));
    }

    public function store(CheckoutRequest $request)
    {
        $order = $this->orderService->checkout(
            user: $request->user(),
            shippingAddress: $request->validated('shipping_address'),
            couponCode: $request->validated('coupon_code'),
        );

        return new OrderResource($order);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $request->validate(['status' => 'required|in:pending,paid,processing,shipped,delivered,cancelled']);

        $order->update(['status' => $request->status]);

        return new OrderResource($order);
    }
}