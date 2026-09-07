<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Services\UserAppSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataManagementController extends Controller
{
    public function __construct(
        private readonly UserAppSettingsService $settingsService,
        private readonly AuthService $authService,
    ) {}

    public function download(Request $request): StreamedResponse
    {
        $user = $request->user();
        $payload = $this->settingsService->buildDataExport($user);

        return response()->streamDownload(
            fn () => $this->streamJson($payload),
            $this->buildFilename($user->id),
            ['Content-Type' => 'application/json']
        );
    }

    public function delete(Request $request): JsonResponse
    {
        $this->authService->deleteAccount($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully',
        ]);
    }

    private function streamJson(array $payload): void
    {
        echo json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );
    }

    private function buildFilename(int $userId): string
    {
        return sprintf(
            'grocery-user-data-%d-%s.json',
            $userId,
            now()->format('Y-m-d')
        );
    }
}