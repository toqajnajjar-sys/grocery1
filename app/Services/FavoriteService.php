<?php

namespace App\Services;

use App\Models\Favorite;
use App\Models\Meal;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FavoriteService
{
    public function getUserFavorites(User $user): Collection
    {
        return $user->favorites()
            ->with(['meal.category', 'meal.subcategory'])
            ->latest()
            ->get();
    }

    public function toggle(User $user, Meal $meal): array
    {
        return DB::transaction(function () use ($user, $meal) {
            $favorite = $user->favorites()
                ->where('meal_id', $meal->id)
                ->first();

            if ($favorite) {
                $favorite->delete();

                return [
                    'message' => 'Removed from favorites',
                    'is_favorited' => false,
                ];
            }

            $user->favorites()->create([
                'meal_id' => $meal->id,
            ]);

            return [
                'message' => 'Added to favorites',
                'is_favorited' => true,
            ];
        });
    }

    public function isFavorited(User $user, Meal $meal): bool
    {
        return $user->favorites()
            ->where('meal_id', $meal->id)
            ->exists();
    }

    public function remove(User $user, Meal $meal): bool
    {
        return $user->favorites()
            ->where('meal_id', $meal->id)
            ->delete() > 0;
    }
}