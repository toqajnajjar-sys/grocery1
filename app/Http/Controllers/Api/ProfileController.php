<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\OperationResponder;
use App\Models\Address;
use App\Models\Order;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private ProfileService $service, private OperationResponder $responses) {}

    public function show(Request $request): JsonResponse
    {
        return $this->responses->respond(function () use ($request) {
            $user = $request->user();
            $user->load(['addresses', 'favorites.meal.category', 'favorites.meal.subcategory']);
            $orders = Order::where('user_id', $user->id)
                ->with(['items.meal.category', 'items.meal.subcategory', 'address'])
                ->latest()->get();
            $history = $orders->map(fn (Order $order) => $this->orderSummary($order));

            return ['success' => true, 'message' => 'Profile retrieved successfully', 'data' => [
                'me' => ['id' => $user->id, 'profile_picture' => $user->profile_image_url,
                    'name' => $user->full_name, 'username' => $user->username, 'firstname' => $user->firstname,
                    'lastname' => $user->lastname, 'gender' => $user->gender,
                    'birthday' => $user->birthday?->format('Y-m-d'), 'email' => $user->email,
                    'phone' => $user->phone, 'country_code' => $user->country_code,
                    'email_verified' => $user->email_verified, 'phone_verified' => $user->phone_verified,
                    'preferred_languages' => $user->preferred_languages ?? [], 'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at],
                'addresses' => $user->addresses()->orderByDesc('is_default')->latest()->get()
                    ->map(fn (Address $address) => $this->address($address)),
                'order_history' => ['orders' => $history,
                    'ordered_at' => $history->map(fn ($order) => $order['placed_at'] ?? $order['created_at'])->values()],
                'in_progress_orders' => $orders->whereNotIn('status', ['cancelled', 'delivered'])
                    ->map(fn (Order $order) => $this->trackedOrder($order))->values(),
                'order_notifications' => $user->notifications()->where(function ($query) {
                    $query->where('data->type', 'order_confirmation')->orWhere('data->type', 'order_shipped')
                        ->orWhere('data->type', 'delivery_updates');
                })->latest()->take(20)->get()->map(fn ($notification) => $this->notification($notification)),
                'settings' => ['privacy_and_security' => ['active_sessions' => $this->sessions($user),
                    'change_password' => ['available' => true], 'change_username' => ['available' => true]]],
                'wishlist' => $user->favorites->map(fn ($favorite) => $this->wishlistItem($favorite))->values(),
            ]];
        }, 'Failed to retrieve profile');
    }

    public function destroySession(Request $request, string $tokenId): JsonResponse
    {
        return $this->responses->respond(fn () => $this->service->destroySession($request->user(), $tokenId), 'Failed to revoke session');
    }

    private function address(Address $address): array
    {
        return ['id' => $address->id, 'label' => $address->label, 'full_name' => $address->full_name,
            'phone' => $address->phone, 'country_code' => $address->country_code,
            'street_address' => $address->street_address, 'building_number' => $address->building_number,
            'floor' => $address->floor, 'apartment' => $address->apartment, 'landmark' => $address->landmark,
            'city' => $address->city, 'state' => $address->state, 'postal_code' => $address->postal_code,
            'country' => $address->country, 'full_address' => $address->full_address ?? null,
            'is_default' => $address->is_default, 'created_at' => $address->created_at, 'updated_at' => $address->updated_at];
    }

    private function orderSummary(Order $order): array
    {
        return ['id' => $order->id, 'order_number' => $order->order_number, 'status' => $order->status,
            'status_description' => $order->status_description, 'total' => (float) $order->total,
            'placed_at' => $order->placed_at?->toIso8601String(), 'created_at' => $order->created_at?->toIso8601String(),
            'item_count' => $order->items->count()];
    }

    private function trackedOrder(Order $order): array
    {
        $stage = match ($order->status) {
            'shipping' => 'arriving', 'out_for_delivery' => 'out_for_delivery',
            'delivered' => 'delivered', default => 'processing',
        };

        return ['id' => $order->id, 'order_number' => $order->order_number, 'status' => $order->status,
            'status_description' => $order->status_description, 'tracking' => ['stage' => $stage,
                'stage_label' => match ($stage) {
                    'arriving' => 'Arriving', 'out_for_delivery' => 'Out for delivery',
                    'delivered' => 'Delivered', default => 'Processing',
                }, 'positions' => [
                    ['stage' => 'arriving', 'label' => 'Arriving', 'completed' => in_array($order->status, ['shipping', 'out_for_delivery', 'delivered']), 'timestamp' => $order->shipping_at?->toIso8601String()],
                    ['stage' => 'out_for_delivery', 'label' => 'Out for delivery', 'completed' => in_array($order->status, ['out_for_delivery', 'delivered']), 'timestamp' => $order->out_for_delivery_at?->toIso8601String()],
                    ['stage' => 'delivered', 'label' => 'Delivered', 'completed' => $order->status === 'delivered', 'timestamp' => $order->delivered_at?->toIso8601String()],
                ]], 'total' => (float) $order->total, 'placed_at' => $order->placed_at?->toIso8601String(),
            'estimated_delivery_time' => $order->estimated_delivery_time?->toIso8601String(),
            'address' => $order->address ? $this->address($order->address) : null,
            'items' => $order->items->map(fn ($item) => ['id' => $item->id,
                'meal' => ['id' => $item->meal->id, 'title' => $item->meal->title, 'image_url' => $item->meal->image_url],
                'quantity' => $item->quantity, 'subtotal' => (float) $item->subtotal])->values()];
    }

    private function notification($notification): array
    {
        $data = $notification->data ?? [];

        return ['id' => $notification->id, 'type' => $data['type'] ?? 'order',
            'title' => $data['title'] ?? 'Order update', 'body' => $data['body'] ?? '',
            'is_read' => $notification->read_at !== null, 'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(), 'action_url' => $data['action_url'] ?? null];
    }

    private function sessions($user): array
    {
        $currentTokenId = $user->currentAccessToken()?->id;

        return $user->tokens()->get()->map(fn ($token) => ['id' => $token->id, 'name' => $token->name,
            'last_used_at' => $token->last_used_at?->toIso8601String(),
            'is_current' => (string) $token->id === (string) $currentTokenId])->all();
    }

    private function wishlistItem($favorite): array
    {
        $meal = $favorite->meal;

        return ['id' => $meal->id, 'title' => $meal->title, 'slug' => $meal->slug,
            'image_url' => $meal->image_url, ...$meal->getApiPriceAttributes(), 'has_offer' => $meal->hasOffer(),
            'category' => $meal->category ? ['id' => $meal->category->id, 'name' => $meal->category->name] : null,
            'is_favorited' => true, 'favorited_at' => $favorite->created_at?->toIso8601String()];
    }
}
