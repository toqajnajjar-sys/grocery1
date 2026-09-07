<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MealResource;
use App\Services\FrequencyService;
use App\Services\MealService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MealController extends Controller
{
    public function __construct(
        private readonly MealService $mealService,
    ) {}

    public function frequency(Request $request): JsonResponse
    {
        $frequencyType = $this->resolveFrequencyType(
            $request->input(
                'frequency_type',
                FrequencyService::FREQUENCY_WEEKLY
            )
        );

        $subcategoryId = $this->resolveSubcategoryId(
            $request->input('subcategory_id')
        );

        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required to view frequency meals.',
            ], 401);
        }

        $meals = $this->mealService->getFrequencyMeals(
            $user,
            $frequencyType,
            $subcategoryId
        );

        $response = [
            'success' => true,
            'message' => 'Frequency meals retrieved successfully',
            'frequency_type' => $frequencyType,
            'data' => MealResource::collection($meals),
        ];

        if ($subcategoryId !== null) {
            $response['subcategory_id'] = $subcategoryId;
        }

        return response()->json($response);
    }

    public function moreToExplore(): JsonResponse
    {
        return $this->mealResponse(
            'More to explore retrieved successfully',
            $this->mealService->getMoreToExplore()
        );
    }

    public function brands(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Brands retrieved successfully',
            'data' => $this->mealService->getBrands(),
        ]);
    }

    public function slider(): JsonResponse
    {
        return $this->mealResponse(
            'Today\'s meals retrieved successfully',
            $this->mealService->getSliderMeals()
        );
    }

    public function bestSells(): JsonResponse
    {
        return $this->mealResponse(
            'Best sells retrieved successfully',
            $this->mealService->getBestSellingMeals()
        );
    }

    public function newProducts(): JsonResponse
    {
        return $this->mealResponse(
            'New products retrieved successfully',
            $this->mealService->getNewProducts()
        );
    }

    public function hot(): JsonResponse
    {
        return $this->mealResponse(
            'Hot meals retrieved successfully',
            $this->mealService->getHotMeals()
        );
    }

    public function today(): JsonResponse
    {
        return $this->mealResponse(
            'Today\'s deals retrieved successfully',
            $this->mealService->getTodayDeals()
        );
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $this->getFilters($request);

        $meals = $this->mealService->getMeals(
            $filters,
            $request->user()
        );

        $totalCount = $meals->count();
        $isEmpty = $totalCount === 0;

        $response = [
            'success' => true,
            'message' => $isEmpty
                ? 'No products match your filters.'
                : 'Meals retrieved successfully',
            'data' => MealResource::collection($meals),
            'total_count' => $totalCount,
            'filters_applied' => $filters,
        ];

        if ($isEmpty) {
            $response['empty_message'] =
                'No products match the applied filters. Try adjusting your search or filters.';
        }

        return response()->json($response);
    }

    public function recommendations(Request $request): JsonResponse
    {
        $limit = max(1, (int) $request->input('limit', 10));

        $meals = $this->mealService->getRecommendations($limit);

        return response()->json([
            'success' => true,
            'message' => 'Meal recommendations retrieved successfully',
            'data' => MealResource::collection($meals),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $meal = $this->mealService->getMeal($id);

        return response()->json([
            'success' => true,
            'message' => 'Meal retrieved successfully',
            'data' => new MealResource($meal),
        ]);
    }

    private function mealResponse(string $message, $meals): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => MealResource::collection($meals),
        ]);
    }

    private function resolveFrequencyType(string $frequencyType): string
    {
        return in_array(
            $frequencyType,
            FrequencyService::VALID_TYPES,
            true
        )
            ? $frequencyType
            : FrequencyService::FREQUENCY_WEEKLY;
    }

    private function resolveSubcategoryId(mixed $subcategoryId): ?int
    {
        return is_numeric($subcategoryId)
            ? (int) $subcategoryId
            : null;
    }

    private function getFilters(Request $request): array
    {
        $sortBy = $request->input('sort_by', 'created_at');

        if ($sortBy === 'newest') {
            $sortBy = 'created_at';
        }

        $sortOrder = strtolower(
            $request->input('sort_order', 'desc')
        ) === 'asc'
            ? 'asc'
            : 'desc';

        return [
            'search' => $request->input('search'),
            'category_id' => $request->input('category_id'),
            'subcategory_id' => $request->input('subcategory_id'),
            'min_price' => $request->input('min_price'),
            'max_price' => $request->input('max_price'),
            'min_rating' => $request->input('min_rating'),
            'brand' => $request->input('brand'),
            'featured' => $request->has('featured')
                ? $request->boolean('featured')
                : null,
            'in_stock' => $request->has('in_stock')
                ? $request->boolean('in_stock')
                : null,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
        ];
    }
}