<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateProfileImageRequest;
use App\Http\Responses\OperationResponder;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;

class UpdateProfileImageController extends Controller
{
    public function __invoke(UpdateProfileImageRequest $request, ProfileService $service, OperationResponder $responses): JsonResponse
    {
        return $responses->respond(fn () => $service->updateImage($request->user(), $request->file('image')), 'Failed to update profile image');
    }
}
