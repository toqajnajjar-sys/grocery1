<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\OperationRejected;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ReviewIndexRequest;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Http\Resources\Api\ReviewResource;
use App\Services\ReviewService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(private ReviewService $reviews) {}

    public function index(ReviewIndexRequest $request): JsonResponse
    {
        return response()->json($this->page($this->reviews->search($request->validated())));
    }

    public function store(StoreReviewRequest $request): JsonResponse
    {
        try {
            $review = $this->reviews->create($request->user(), $request->validated());

            return response()->json(['success' => true, 'message' => 'Review submitted successfully. Waiting for admin approval.',
                'data' => new ReviewResource($review)], 201);
        } catch (OperationRejected $e) {
            return response()->json($e->details, 400);
        }
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['success' => true, 'data' => new ReviewResource($this->reviews->find($id))]);
    }

    public function update(UpdateReviewRequest $request, string $id): JsonResponse
    {
        try {
            $review = $this->reviews->update($request->user(), $id, $request->validated());

            return response()->json(['success' => true, 'message' => 'Review updated successfully', 'data' => new ReviewResource($review)]);
        } catch (OperationRejected $e) {
            return response()->json($e->details, 403);
        }
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $this->reviews->delete($request->user(), $id);

            return response()->json(['success' => true, 'message' => 'Review deleted successfully']);
        } catch (OperationRejected $e) {
            return response()->json($e->details, 403);
        }
    }

    public function getMealReviews(string $mealId, ReviewIndexRequest $request): JsonResponse
    {
        $result = $this->reviews->forMeal($mealId, $request->validated('per_page', 10));

        return response()->json($this->page($result['reviews']) + ['meal' => $result['meal']]);
    }

    public function getUserReviews(ReviewIndexRequest $request): JsonResponse
    {
        return response()->json($this->page($this->reviews->forUser(
            $request->validated('user_id', $request->user()->id), $request->validated('per_page', 10))));
    }

    public function getMealReviewStats(string $mealId): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->reviews->statistics($mealId)]);
    }

    private function page(LengthAwarePaginator $reviews): array
    {
        return ['success' => true, 'data' => ReviewResource::collection($reviews),
            'meta' => ['current_page' => $reviews->currentPage(), 'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(), 'total' => $reviews->total()]];
    }
}
