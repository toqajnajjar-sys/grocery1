<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateProfileInfoRequest;
use App\Http\Responses\OperationResponder;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;

class UpdateProfileInfoController extends Controller
{
    public function __invoke(UpdateProfileInfoRequest $request, ProfileService $service, OperationResponder $responses): JsonResponse
    {
        return $responses->respond(fn () => $service->updateInfo($request->user(), $request->validated()), 'Failed to update profile');
    }
}
