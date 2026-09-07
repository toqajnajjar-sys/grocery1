<?php

namespace App\Services;

use App\Exceptions\OperationRejected;
use App\Models\Meal;
use App\Models\Review;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ReviewService
{
    public function __construct(private QueryFilterPipeline $filters) {}

    public function search(array $input): LengthAwarePaginator
    {
        $input += ['approved_only' => true];

        return $this->filters->apply('reviews', Review::with(['user', 'meal'])->latest(), $input)
            ->paginate($input['per_page'] ?? 15);
    }

    public function create(User $user, array $data): Review
    {
        if (Review::hasUserReviewed($user->id, $data['meal_id'])) {
            throw new OperationRejected('invalid_operation', ['success' => false, 'message' => 'You have already reviewed this meal']);
        }

        return Review::create($data + ['user_id' => $user->id, 'is_approved' => false])->load(['user', 'meal']);
    }

    public function find(string $id): Review
    {
        return Review::with(['user', 'meal'])->findOrFail($id);
    }

    public function update(User $user, string $id, array $data): Review
    {
        $review = $this->editable($user, $id);
        $review->update($data);

        return $review->load(['user', 'meal']);
    }

    public function delete(User $user, string $id): void
    {
        $this->editable($user, $id)->delete();
    }

    private function editable(User $user, string $id): Review
    {
        $review = Review::findOrFail($id);
        if ($user->id !== $review->user_id && ! $user->is_admin) {
            throw new OperationRejected('forbidden', ['success' => false, 'message' => 'Unauthorized']);
        }

        return $review;
    }

    public function forMeal(string $mealId, int $perPage): array
    {
        $meal = Meal::findOrFail($mealId);

        return [
            'meal' => ['id' => $meal->id, 'name' => $meal->name,
                'average_rating' => round(Review::getAverageRating($mealId), 1),
                'total_reviews' => Review::getTotalReviews($mealId)],
            'reviews' => Review::with('user')->where('meal_id', $mealId)->approved()->latest()->paginate($perPage),
        ];
    }

    public function forUser(int $userId, int $perPage): LengthAwarePaginator
    {
        return Review::with('meal')->where('user_id', $userId)->latest()->paginate($perPage);
    }

    public function statistics(string $mealId): array
    {
        $stats = Review::where('meal_id', $mealId)->approved()->selectRaw('
            COUNT(*) as total_reviews, AVG(rating) as average_rating,
            COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star,
            COUNT(CASE WHEN rating = 4 THEN 1 END) as four_star,
            COUNT(CASE WHEN rating = 3 THEN 1 END) as three_star,
            COUNT(CASE WHEN rating = 2 THEN 1 END) as two_star,
            COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star
        ')->first();

        return ['total_reviews' => (int) $stats->total_reviews,
            'average_rating' => round($stats->average_rating ?? 0, 1),
            'rating_distribution' => ['five_star' => (int) $stats->five_star,
                'four_star' => (int) $stats->four_star, 'three_star' => (int) $stats->three_star,
                'two_star' => (int) $stats->two_star, 'one_star' => (int) $stats->one_star]];
    }
}
