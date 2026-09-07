namespace App\Actions\Dashboard;

use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class GetTopPurchasesAction
{
    public function execute(User $user, int $limit = 10): Collection
    {
        return OrderItem::whereHas('order', fn ($q) => $q->where('user_id', $user->id)->where('status', '!=', 'cancelled'))
            ->with('meal.category', 'meal.subcategory')
            ->select('meal_id', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(subtotal) as total_spent'))
            ->groupBy('meal_id')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();
    }
}