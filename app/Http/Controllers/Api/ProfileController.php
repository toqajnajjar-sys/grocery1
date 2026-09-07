<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateProfileImageRequest;
use App\Http\Requests\Api\UpdateProfileInfoRequest;
use App\Http\Responses\OperationResponder;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private ProfileService $service, private OperationResponder $responses) {}

    public function show(Request $request): JsonResponse
    {
        return $this->responses->respond(fn () => $this->service->show($request->user()), 'Failed to retrieve profile');
    }

    public function updateImage(UpdateProfileImageRequest $request): JsonResponse
    {
        return $this->responses->respond(fn () => $this->service->updateImage($request->user(), $request->file('image')), 'Failed to update profile image');
    }

    public function updateInfo(UpdateProfileInfoRequest $request): JsonResponse
    {
        return $this->responses->respond(fn () => $this->service->updateInfo($request->user(), $request->validated()), 'Failed to update profile');
    }

    public function deleteImage(Request $request): JsonResponse
    {
        return $this->responses->respond(fn () => $this->service->deleteImage($request->user()), 'Failed to delete profile image');
    }

    public function sessions(Request $request): JsonResponse
    {
        return $this->responses->respond(fn () => $this->service->sessions($request->user()), 'Failed to retrieve sessions');
    }

    public function destroySession(Request $request, string $tokenId): JsonResponse
    {
        return $this->responses->respond(fn () => $this->service->destroySession($request->user(), $tokenId), 'Failed to revoke session');
    }
}
