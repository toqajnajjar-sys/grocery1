<?php

namespace App\Services;

use App\Exceptions\OperationRejected;
use App\Factories\UploadStrategyFactory;
use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    public function __construct(private UploadStrategyFactory $uploads) {}

    public function show(User $user): array
    {

        $user->load(['addresses', 'favorites.meal.category', 'favorites.meal.subcategory']);

        $addresses = $user->addresses()
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Address $a) => $this->formatAddress($a));

        $allOrders = Order::where('user_id', $user->id)
            ->with(['items.meal.category', 'items.meal.subcategory', 'address'])
            ->orderBy('created_at', 'desc')
            ->get();

        $orderHistory = $allOrders->map(fn (Order $o) => $this->formatOrderSummary($o));

        $inProgressOrders = $allOrders->whereNotIn('status', ['cancelled', 'delivered']);
        $inProgressWithTracking = $inProgressOrders->map(fn (Order $o) => $this->formatOrderWithTracking($o))->values();

        $orderNotifications = $user->notifications()
            ->where(function ($q) {
                $q->where('data->type', 'order_confirmation')
                    ->orWhere('data->type', 'order_shipped')
                    ->orWhere('data->type', 'delivery_updates');
            })
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get()
            ->map(fn ($n) => $this->formatNotification($n));

        $sessions = $this->formatSessions($user);
        $wishlist = $user->favorites->map(fn ($f) => $this->formatWishlistItem($f))->values();

        return [
            'success' => true,
            'message' => 'Profile retrieved successfully',
            'data' => [
                'me' => [
                    'id' => $user->id,
                    'profile_picture' => $user->profile_image_url,
                    'name' => $user->full_name,
                    'username' => $user->username,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'gender' => $user->gender,
                    'birthday' => $user->birthday?->format('Y-m-d'),
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'country_code' => $user->country_code,
                    'email_verified' => $user->email_verified,
                    'phone_verified' => $user->phone_verified,
                    'preferred_languages' => $user->preferred_languages ?? [],
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
                'addresses' => $addresses,
                'order_history' => [
                    'orders' => $orderHistory,
                    'ordered_at' => $orderHistory->map(fn ($o) => $o['placed_at'] ?? $o['created_at'])->values(),
                ],
                'in_progress_orders' => $inProgressWithTracking,
                'order_notifications' => $orderNotifications,
                'settings' => [
                    'privacy_and_security' => [
                        'active_sessions' => $sessions,
                        'change_password' => ['available' => true],
                        'change_username' => ['available' => true],
                    ],
                ],
                'wishlist' => $wishlist,
            ],
        ];

    }

    public function updateImage(User $user, UploadedFile $image): array
    {
        // Delete old image if exists
        if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
            Storage::disk('public')->delete($user->profile_image);
        }

        // Store new image

        $path = $this->uploads->make('profile')->store($image);

        // Update user
        $user->update(['profile_image' => $path]);

        return [
            'success' => true,
            'message' => 'Profile image updated successfully',
            'data' => [
                'profile_image' => $user->profile_image,
                'profile_image_url' => $user->profile_image_url,
            ],
        ];

    }

    public function updateInfo(User $user, array $data): array
    {
        // Remove empty values (except preferred_languages which can be empty array)
        $data = array_filter($data, function ($value, $key) {
            if ($key === 'preferred_languages') {
                return true; // Always include preferred_languages even if empty
            }

            return $value !== null && $value !== '';
        }, ARRAY_FILTER_USE_BOTH);

        if (empty($data)) {
            throw new OperationRejected('invalid_operation', [
                'success' => false,
                'message' => 'No data provided to update',
            ]);
        }

        $user->update($data);

        return [
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'full_name' => $user->full_name,
                'gender' => $user->gender,
                'birthday' => $user->birthday?->format('Y-m-d'),
                'email' => $user->email,
                'phone' => $user->phone,
                'country_code' => $user->country_code,
                'preferred_languages' => $user->preferred_languages ?? [],
                'profile_image_url' => $user->profile_image_url,
                'updated_at' => $user->updated_at,
            ],
        ];

    }

    public function deleteImage(User $user): array
    {

        if (! $user->profile_image) {
            throw new OperationRejected('not_found', [
                'success' => false,
                'message' => 'No profile image to delete',
            ]);
        }

        // Delete image from storage
        if (Storage::disk('public')->exists($user->profile_image)) {
            Storage::disk('public')->delete($user->profile_image);
        }

        // Update user
        $user->update(['profile_image' => null]);

        return [
            'success' => true,
            'message' => 'Profile image deleted successfully',
        ];

    }

    public function sessions(User $user): array
    {

        $currentTokenId = $user->currentAccessToken()?->id;

        $tokens = $user->tokens()->get()->map(function ($token) use ($currentTokenId) {
            return [
                'id' => $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'is_current' => (string) $token->id === (string) $currentTokenId,
                'created_at' => $token->created_at?->toIso8601String(),
            ];
        });

        return [
            'success' => true,
            'message' => 'Sessions retrieved successfully',
            'data' => $tokens,
        ];
    }

    public function destroySession(User $user, string $tokenId): array
    {

        $currentTokenId = $user->currentAccessToken()?->id;

        if ((string) $tokenId === (string) $currentTokenId) {
            throw new OperationRejected('invalid_operation', [
                'success' => false,
                'message' => 'Cannot revoke your current session from this request. Use logout instead.',
            ]);
        }

        $token = $user->tokens()->find($tokenId);
        if (! $token) {
            throw new OperationRejected('not_found', [
                'success' => false,
                'message' => 'Session not found',
            ]);
        }

        $token->delete();

        return [
            'success' => true,
            'message' => 'Session revoked successfully',
        ];
    }

    private function formatAddress(Address $address): array
    {
        return [
            'id' => $address->id,
            'label' => $address->label,
            'full_name' => $address->full_name,
            'phone' => $address->phone,
            'country_code' => $address->country_code,
            'street_address' => $address->street_address,
            'building_number' => $address->building_number,
            'floor' => $address->floor,
            'apartment' => $address->apartment,
            'landmark' => $address->landmark,
            'city' => $address->city,
            'state' => $address->state,
            'postal_code' => $address->postal_code,
            'country' => $address->country,
            'full_address' => $address->full_address ?? null,
            'is_default' => $address->is_default,
            'created_at' => $address->created_at,
            'updated_at' => $address->updated_at,
        ];
    }

    private function formatOrderSummary(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'status_description' => $order->status_description,
            'total' => (float) $order->total,
            'placed_at' => $order->placed_at?->toIso8601String(),
            'created_at' => $order->created_at?->toIso8601String(),
            'item_count' => $order->items->count(),
        ];
    }

    private function formatOrderWithTracking(Order $order): array
    {
        $trackingStage = match ($order->status) {
            'shipping' => 'arriving',
            'out_for_delivery' => 'out_for_delivery',
            'delivered' => 'delivered',
            default => 'processing',
        };

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'status_description' => $order->status_description,
            'tracking' => [
                'stage' => $trackingStage,
                'stage_label' => match ($trackingStage) {
                    'arriving' => 'Arriving',
                    'out_for_delivery' => 'Out for delivery',
                    'delivered' => 'Delivered',
                    default => 'Processing',
                },
                'positions' => [
                    ['stage' => 'arriving', 'label' => 'Arriving', 'completed' => in_array($order->status, ['shipping', 'out_for_delivery', 'delivered']), 'timestamp' => $order->shipping_at?->toIso8601String()],
                    ['stage' => 'out_for_delivery', 'label' => 'Out for delivery', 'completed' => in_array($order->status, ['out_for_delivery', 'delivered']), 'timestamp' => $order->out_for_delivery_at?->toIso8601String()],
                    ['stage' => 'delivered', 'label' => 'Delivered', 'completed' => $order->status === 'delivered', 'timestamp' => $order->delivered_at?->toIso8601String()],
                ],
            ],
            'total' => (float) $order->total,
            'placed_at' => $order->placed_at?->toIso8601String(),
            'estimated_delivery_time' => $order->estimated_delivery_time?->toIso8601String(),
            'address' => $order->address ? $this->formatAddress($order->address) : null,
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'meal' => ['id' => $item->meal->id, 'title' => $item->meal->title, 'image_url' => $item->meal->image_url],
                'quantity' => $item->quantity,
                'subtotal' => (float) $item->subtotal,
            ])->values(),
        ];
    }

    private function formatNotification($notification): array
    {
        $data = $notification->data ?? [];

        return [
            'id' => $notification->id,
            'type' => $data['type'] ?? 'order',
            'title' => $data['title'] ?? 'Order update',
            'body' => $data['body'] ?? '',
            'is_read' => $notification->read_at !== null,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
            'action_url' => $data['action_url'] ?? null,
        ];
    }

    private function formatSessions($user): array
    {
        $currentTokenId = $user->currentAccessToken()?->id;

        return $user->tokens()->get()->map(function ($token) use ($currentTokenId) {
            return [
                'id' => $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'is_current' => (string) $token->id === (string) $currentTokenId,
            ];
        })->all();
    }

    private function formatWishlistItem($favorite): array
    {
        $meal = $favorite->meal;

        return [
            'id' => $meal->id,
            'title' => $meal->title,
            'slug' => $meal->slug,
            'image_url' => $meal->image_url,
            ...$meal->getApiPriceAttributes(),
            'has_offer' => $meal->hasOffer(),
            'category' => $meal->category ? ['id' => $meal->category->id, 'name' => $meal->category->name] : null,
            'is_favorited' => true,
            'favorited_at' => $favorite->created_at?->toIso8601String(),
        ];
    }
}
