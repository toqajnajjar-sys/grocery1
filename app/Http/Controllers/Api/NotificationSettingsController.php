<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateNotificationCategoryRequest;
use App\Http\Requests\Api\UpdateNotificationSettingsRequest;
use App\Services\NotificationSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationSettingsController extends Controller
{
    public function __construct(
        private readonly NotificationSettingsService $settingsService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->settingsService->getSettings(
                $request->user()
            ),
        ]);
    }

    public function update(
        UpdateNotificationSettingsRequest $request
    ): JsonResponse {
        $settings = $this->settingsService->update(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Notification settings updated successfully',
            'data' => $settings,
        ]);
    }

    public function updateCategory(
        UpdateNotificationCategoryRequest $request,
        string $category
    ): JsonResponse {
        $settings = $this->settingsService->updateCategory(
            $request->user(),
            $category,
            (bool) $request->validated()['enabled']
        );

        if ($settings === null) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid category',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification settings updated successfully',
            'data' => $settings,
        ]);
    }
}