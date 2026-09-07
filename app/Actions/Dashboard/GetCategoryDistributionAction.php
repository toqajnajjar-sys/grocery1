namespace App\Actions\Dashboard;

use App\Models\OrderItem;
use App\Models\User;

class GetCategoryDistributionAction
{
    public function execute(User $user): array
    {
        $orderItems = OrderItem::whereHas('order', fn ($q) => $q->where('user_id', $user->id)->where('status', '!=', 'cancelled'))
            ->with('meal.category')
            ->get();

        $categoryTotals = [];
        $totalItems = 0;

        foreach ($orderItems as $item) {
            if ($item->meal && $item->meal->category) {
                $categoryId = $item->meal->category->id;
                $categoryName = $item->meal->category->name;
                $quantity = $item->quantity;

                if (! isset($categoryTotals[$categoryId])) {
                    $categoryTotals[$categoryId] = [
                        'category_id'    => $categoryId,
                        'category_name'  => $categoryName,
                        'total_quantity' => 0,
                    ];
                }

                $categoryTotals[$categoryId]['total_quantity'] += $quantity;
                $totalItems += $quantity;
            }
        }

        $distribution = [];
        foreach ($categoryTotals as $data) {
            $percentage = $totalItems > 0 ? round(($data['total_quantity'] / $totalItems) * 100, 1) : 0;
            $distribution[] = [
                'category_id'    => $data['category_id'],
                'category_name'  => $data['category_name'],
                'total_quantity' => $data['total_quantity'],
                'percentage'     => $percentage,
            ];
        }

        usort($distribution, fn ($a, $b) => $b['percentage'] <=> $a['percentage']);

        return $distribution;
    }
}