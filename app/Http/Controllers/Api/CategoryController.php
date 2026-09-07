namespace App\Http\Controllers\Api;

use App\Actions\Category\GetCategoriesAction;
use App\Actions\Category\GetCategoryDetailAction;
use App\Actions\Category\GetCategoryMealsAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\MealResource;
use App\Traits\V1\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponse;

    /**
     * Get all categories
     */
    public function index(GetCategoriesAction $action): JsonResponse
    {
        $categories = $action->execute();

        return self::successResponse(
            'Categories retrieved successfully',
            CategoryResource::collection($categories)
        );
    }

    /**
     * Get single category with meals
     */
    public function show(string $id, GetCategoryDetailAction $action): JsonResponse
    {
        $category = $action->execute($id);

        return self::successResponse(
            'Category retrieved successfully',
            new CategoryResource($category)
        );
    }

    /**
     * Get meals by category (paginated)
     */
    public function meals(string $id, Request $request, GetCategoryMealsAction $action): JsonResponse
    {
        $result = $action->execute($id, $request);
        
        $category = $result['category'];
        $paginator = $result['paginator'];
        $total = $paginator->total();

        $data = [
            'category' => [
                'id'   => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ],
            'meals' => MealResource::collection($paginator->items()),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $total,
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
        ];

        if ($total === 0) {
            $data['empty_message'] = 'No products match the applied filters. Try adjusting your filters.';
        }

        $message = $total === 0 ? 'No products match your filters.' : 'Meals retrieved successfully';

        return self::successResponse($message, $data);
    }
}