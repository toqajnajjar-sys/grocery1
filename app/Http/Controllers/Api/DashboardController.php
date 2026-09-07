namespace App\Http\Controllers\Api;

use App\Actions\Dashboard\GetCategoryDistributionAction;
use App\Actions\Dashboard\GetDashboardOverviewAction;
use App\Actions\Dashboard\GetShoppingInsightsAction;
use App\Actions\Dashboard\GetTopPurchasesAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\DashboardOverviewResource;
use App\Http\Resources\Api\OrderResource;
use App\Http\Resources\Api\TopPurchaseResource;
use App\Models\Order;
use App\Traits\V1\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    /**
     * Get dashboard statistics and insights.
     */
    public function index(
        Request $request,
        GetDashboardOverviewAction $overviewAction,
        GetShoppingInsightsAction $insightsAction,
        GetCategoryDistributionAction $categoryAction,
        GetTopPurchasesAction $topPurchasesAction
    ): JsonResponse {
        $user = $request->user();

        $recentOrders = Order::where('user_id', $user->id)
            ->with(['items.meal.category', 'items.meal.subcategory', 'address'])
            ->latest()
            ->limit(5)
            ->get();

        $dashboardData = [
            'overview'              => new DashboardOverviewResource($overviewAction->execute($user)),
            'shopping_insights'     => $insightsAction->execute($user),
            'category_distribution' => $categoryAction->execute($user),
            'recent_orders'         => OrderResource::collection($recentOrders),
            'top_purchases'         => TopPurchaseResource::collection($topPurchasesAction->execute($user)),
        ];

        return self::successResponse(
            'Dashboard data retrieved successfully',
            $dashboardData
        );
    }
}