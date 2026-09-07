namespace App\Actions\Dashboard;

use App\Models\Order;
use App\Models\User;

class GetDashboardOverviewAction
{
    public function execute(User $user): array
    {
        $activeOrder = Order::where('user_id', $user->id)
            ->whereNotIn('status', ['cancelled', 'delivered'])
            ->with(['items.meal', 'address'])
            ->latest()
            ->first();

        $cart = $user->activeCart()->with('items')->first();
        $cartData = [
            'items_count' => 0,
            'total'       => 0.0,
            'last_updated' => null,
        ];

        if ($cart) {
            $cart->calculateTotals();
            $cartData = [
                'items_count'  => $cart->items->sum('quantity'),
                'total'        => (float) $cart->total,
                'last_updated' => $cart->updated_at,
            ];
        }

        $upcomingDelivery = Order::where('user_id', $user->id)
            ->whereIn('status', ['placed', 'processing', 'shipping', 'out_for_delivery'])
            ->whereNotNull('estimated_delivery_time')
            ->orderBy('estimated_delivery_time', 'asc')
            ->first();

        return [
            'user'              => $user,
            'tracking_order'    => $activeOrder,
            'current_cart'      => $cartData,
            'upcoming_delivery' => $upcomingDelivery,
        ];
    }
}