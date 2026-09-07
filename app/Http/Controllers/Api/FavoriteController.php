<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FavoriteResource;
use App\Models\Meal;
use App\Services\FavoriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function __construct(
        private readonly FavoriteService $favoriteService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $favorites = $this->favoriteService
            ->getUserFavorites($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Favorites retrieved successfully',
            'data' => FavoriteResource::collection($favorites),
            'total_count' => $favorites->count(),
        ]);
    }

    public function toggle(Request $request, string $mealId): JsonResponse
    {
        $meal = Meal::findOrFail($mealId);

        $result = $this->favoriteService->toggle(
            $request->user(),
            $meal
        );

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'meal_id' => $meal->id,
                'is_favorited' => $result['is_favorited'],
            ],
        ]);
    }

    public function check(Request $request, string $mealId): JsonResponse
    {
        $meal = Meal::findOrFail($mealId);

        $isFavorited = $this->favoriteService->isFavorited(
            $request->user(),
            $meal
        );

        return response()->json([
            'success' => true,
            'data' => [
                'meal_id' => $meal->id,
                'is_favorited' => $isFavorited,
            ],
        ]);
    }

    public function remove(Request $request, string $mealId): JsonResponse
    {
        $meal = Meal::findOrFail($mealId);

        $removed = $this->favoriteService->remove(
            $request->user(),
            $meal
        );

        if (! $removed) {
            return response()->json([
                'success' => false,
                'message' => 'Meal was not in favorites',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Removed from favorites',
            'data' => [
                'meal_id' => $meal->id,
                'is_favorited' => false,
            ],
        ]);
    }
}