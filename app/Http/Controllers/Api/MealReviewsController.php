<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ReviewIndexRequest;
use App\Http\Resources\Api\ReviewResource;
use App\Models\Meal;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;

class MealReviewsController extends Controller
{
    public function __invoke(Meal $meal, ReviewIndexRequest $request, ReviewService $reviews): JsonResponse
    {
        $result = $reviews->forMeal($meal, $request->validated('per_page', 10));
        $page = $result['reviews'];

        return response()->json(['success' => true, 'data' => ReviewResource::collection($page),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(), 'total' => $page->total()], 'meal' => $result['meal']]);
    }
}
