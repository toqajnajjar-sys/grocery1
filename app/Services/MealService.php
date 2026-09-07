<?php

namespace App\Services;

use App\Models\Meal;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class MealService
{
    public function __construct(
        private readonly FrequencyService $frequencyService,
    ) {}

    public function getFrequencyMeals(
        User $user,
        string $frequencyType,
        ?int $subcategoryId
    ): Collection {
        return $this->frequencyService->getFrequentlyOrderedMeals(
            $user,
            $frequencyType,
            50,
            $subcategoryId
        );
    }

    public function getMoreToExplore(): Collection
    {
        return Meal::with('category')
            ->available()
            ->latest()
            ->get();
    }

    public function getBrands(): Collection
    {
        return Meal::distinct()->pluck('brand');
    }

    public function getSliderMeals(): Collection
    {
        return Meal::with('category')
            ->available()
            ->latest()
            ->get();
    }

    public function getBestSellingMeals(): Collection
    {
        return Meal::with('category')
            ->available()
            ->take(10)
            ->get();
    }

    public function getNewProducts(): Collection
    {
        return Meal::with('category')
            ->available()
            ->latest()
            ->get();
    }

    public function getHotMeals(): Collection
    {
        return Meal::with('category')
            ->available()
            ->hot()
            ->latest()
            ->get();
    }

    public function getTodayDeals(): Collection
    {
        return Meal::with('category')
            ->available()
            ->withActiveDiscount()
            ->latest()
            ->get();
    }

    public function getMeals(array $filters, ?User $user): Collection
    {
        $query = Meal::with(['category', 'subcategory'])
            ->available();

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);

        $meals = $query->get();

        if ($user) {
            $favoriteMealIds = $user->favorites()
                ->pluck('meal_id')
                ->toArray();

            $meals->each(function (Meal $meal) use ($favoriteMealIds) {
                $meal->setAttribute(
                    'is_favorited',
                    in_array($meal->id, $favoriteMealIds, true)
                );
            });
        }

        return $meals;
    }

    public function getRecommendations(int $limit): Collection
    {
        $featuredLimit = (int) ceil($limit / 2);

        $featuredMeals = Meal::with('category')
            ->available()
            ->featured()
            ->whereNotNull('discount_price')
            ->inRandomOrder()
            ->limit($featuredLimit)
            ->get();

        $randomMeals = Meal::with('category')
            ->available()
            ->whereNotIn('id', $featuredMeals->pluck('id'))
            ->inRandomOrder()
            ->limit($limit - $featuredMeals->count())
            ->get();

        return $featuredMeals
            ->merge($randomMeals)
            ->shuffle()
            ->take($limit);
    }

    public function getMeal(string $id): Meal
    {
        return Meal::with([
            'category',
            'subcategory',
            'reviews' => fn ($query) => $query
                ->approved()
                ->with('user:id,username,firstname,lastname')
                ->latest(),
        ])->findOrFail($id);
    }

    private function applyFilters($query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['subcategory_id'])) {
            $query->where('subcategory_id', $filters['subcategory_id']);
        }

        if (isset($filters['featured'])) {
            $filters['featured']
                ? $query->featured()
                : $query->where('is_featured', false);
        }

        if (isset($filters['in_stock'])) {
            $filters['in_stock']
                ? $query->inStock()
                : $query->outOfStock();
        }

        if (isset($filters['min_price'])) {
            $query->whereRaw(
                'COALESCE(discount_price, price) >= ?',
                [$filters['min_price']]
            );
        }

        if (isset($filters['max_price'])) {
            $query->whereRaw(
                'COALESCE(discount_price, price) <= ?',
                [$filters['max_price']]
            );
        }

        if (isset($filters['min_rating'])) {
            $query->where('rating', '>=', $filters['min_rating']);
        }

        if (! empty($filters['brand'])) {
            $query->where('brand', $filters['brand']);
        }
    }

    private function applySorting($query, array $filters): void
    {
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        if ($sortBy === 'newest') {
            $sortBy = 'created_at';
            $sortOrder = 'desc';
        }

        $allowedFields = [
            'created_at',
            'price',
            'rating',
            'title',
            'sold_count',
        ];

        if (! in_array($sortBy, $allowedFields, true)) {
            $query->latest();

            return;
        }

        if ($sortBy === 'price') {
            $query->orderByRaw(
                "COALESCE(discount_price, price) {$sortOrder}"
            );

            return;
        }

        $query->orderBy($sortBy, $sortOrder);
    }
}