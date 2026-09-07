namespace App\Actions\Dashboard;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\Carbon;

class GetShoppingInsightsAction
{
    public function execute(User $user): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $monthlyOrdersQuery = Order::where('user_id', $user->id)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->where('status', '!=', 'cancelled');

        $monthlySpend = (float) $monthlyOrdersQuery->sum('total');
        $ordersThisMonth = $monthlyOrdersQuery->get();
        $ordersCount = $ordersThisMonth->count();

        $averageDaysBetweenOrders = 0;
        if ($ordersCount > 1) {
            $orderDates = $ordersThisMonth->pluck('created_at')->sort()->values();
            $totalDays = 0;
            $intervals = 0;

            for ($i = 1; $i < $orderDates->count(); $i++) {
                $totalDays += $orderDates[$i]->diffInDays($orderDates[$i - 1]);
                $intervals++;
            }

            $averageDaysBetweenOrders = $intervals > 0 ? round($totalDays / $intervals, 1) : 0;
        }

        $orderDiscounts = Order::where('user_id', $user->id)
            ->where('status', '!=', 'cancelled')
            ->sum('discount');

        $mealSavings = OrderItem::whereHas('order', fn ($q) => $q->where('user_id', $user->id)->where('status', '!=', 'cancelled'))
            ->with('meal')
            ->get()
            ->sum(function ($item) {
                if ($item->meal && $item->meal->discount_price) {
                    return ($item->meal->price - $item->meal->discount_price) * $item->quantity;
                }
                return 0;
            });

        $totalSavings = (float) ($orderDiscounts + $mealSavings);
        $averageOrderValue = $ordersCount > 0 ? round($monthlySpend / $ordersCount, 2) : 0.0;

        return [
            'monthly_spend' => $monthlySpend,
            'orders_this_month' => [
                'count'                       => $ordersCount,
                'average_days_between_orders' => $averageDaysBetweenOrders,
            ],
            'total_savings'       => $totalSavings,
            'average_order_value' => $averageOrderValue,
        ];
    }
}