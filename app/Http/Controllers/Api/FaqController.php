<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFaqRequest;
use App\Http\Requests\UpdateFaqRequest;
use App\Http\Resources\FaqCollection;
use App\Http\Resources\FaqResource;
use App\Models\Faq;
use App\Services\FaqService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function __construct(
        private readonly FaqService $faqService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $faqs = $this->faqService->getFaqs(
            $request->input('category'),
            $request->boolean('active_only', true),
            $request->input('search'),
            (int) $request->input('per_page', 15),
        );

        $response = [
            'data' => new FaqCollection($faqs),
        ];

        if ($request->boolean('with_categories')) {
            $response['categories'] = $this->faqService->getCategories();
        }

        return response()->json($response);
    }

    public function store(StoreFaqRequest $request): JsonResponse
    {
        $faq = $this->faqService->create($request->validated());

        return response()->json([
            'message' => 'FAQ created successfully',
            'data' => new FaqResource($faq),
        ], 201);
    }

    public function show(Faq $faq): FaqResource
    {
        return new FaqResource($faq);
    }

    public function update(
        UpdateFaqRequest $request,
        Faq $faq
    ): JsonResponse {
        $faq = $this->faqService->update(
            $faq,
            $request->validated()
        );

        return response()->json([
            'message' => 'FAQ updated successfully',
            'data' => new FaqResource($faq),
        ]);
    }

    public function destroy(Faq $faq): JsonResponse
    {
        $this->faqService->delete($faq);

        return response()->json([
            'message' => 'FAQ deleted successfully',
        ]);
    }

    public function categories(): JsonResponse
    {
        return response()->json([
            'data' => $this->faqService->getCategories(),
        ]);
    }

    public function byCategory(string $category)
    {
        return FaqResource::collection(
            $this->faqService->getByCategory($category)
        );
    }
}