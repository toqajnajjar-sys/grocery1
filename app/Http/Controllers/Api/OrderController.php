<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Responses\OperationResponder;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private OrderService $service, private OperationResponder $responses) {}

    public function show(Request $request, string $id): JsonResponse
    {
        return $this->responses->respond(fn () => $this->service->show($request->user(), $id), 'Failed to retrieve order');
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        return $this->responses->respond(fn () => $this->service->store($request->user(), $request->validated()), 'Failed to create order', 201);
    }

    public function index(Request $request): JsonResponse
    {
        return $this->responses->respond(function () use ($request) {
            $orders = Order::where('user_id', $request->user()->id)
                ->with(['items.meal.category', 'items.meal.subcategory', 'address'])
                ->latest()->get()->map(fn (Order $order) => $this->orderData($order));

            return ['success' => true, 'message' => 'Orders retrieved successfully',
                'data' => $orders, 'total_count' => $orders->count()];
        }, 'Failed to retrieve orders');
    }

    private function orderData(Order $order): array
    {
        return ['id' => $order->id, 'order_number' => $order->order_number,
            'payment_method' => $order->payment_method, 'stripe_payment_intent_id' => $order->stripe_payment_intent_id,
            'delivery_type' => $order->delivery_type, 'status' => $order->status,
            'status_position' => $order->status_position, 'status_description' => $order->status_description,
            'items' => $order->items->map(fn ($item) => ['id' => $item->id,
                'meal' => ['id' => $item->meal->id, 'title' => $item->meal->title, 'slug' => $item->meal->slug,
                    'image_url' => $item->meal->image_url, ...$item->meal->getApiPriceAttributes(),
                    'category' => $item->meal->category ? ['id' => $item->meal->category->id, 'name' => $item->meal->category->name] : null,
                    'subcategory' => $item->meal->subcategory ? ['id' => $item->meal->subcategory->id, 'name' => $item->meal->subcategory->name] : null],
                'quantity' => $item->quantity, 'unit_price' => (float) $item->unit_price,
                'discount_amount' => (float) $item->discount_amount, 'subtotal' => (float) $item->subtotal]),
            'address' => $order->address ? ['id' => $order->address->id, 'label' => $order->address->label,
                'full_name' => $order->address->full_name, 'phone' => $order->address->phone,
                'country_code' => $order->address->country_code, 'street_address' => $order->address->street_address,
                'building_number' => $order->address->building_number, 'floor' => $order->address->floor,
                'apartment' => $order->address->apartment, 'landmark' => $order->address->landmark,
                'city' => $order->address->city, 'state' => $order->address->state,
                'postal_code' => $order->address->postal_code, 'country' => $order->address->country,
                'full_address' => $order->address->full_address, 'latitude' => $order->address->latitude,
                'longitude' => $order->address->longitude] : null,
            'subtotal' => $order->subtotal, 'tax' => $order->tax, 'discount' => $order->discount,
            'shipping_fee' => (float) ($order->shipping_fee ?? 0), 'total' => $order->total,
            'notes' => $order->notes, 'created_at' => $order->created_at, 'updated_at' => $order->updated_at,
            'placed_at' => $order->placed_at, 'processing_at' => $order->processing_at,
            'shipping_at' => $order->shipping_at, 'out_for_delivery_at' => $order->out_for_delivery_at,
            'delivered_at' => $order->delivered_at, 'estimated_delivery_time' => $order->estimated_delivery_time,
            'special_note' => $order->special_note, 'schedule_delivery' => $order->schedule_delivery,
            'delivery_speed' => $order->delivery_speed];
    }
}
