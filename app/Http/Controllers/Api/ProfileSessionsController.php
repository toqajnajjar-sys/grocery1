<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\OperationResponder;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileSessionsController extends Controller
{
    public function __invoke(Request $request, ProfileService $service, OperationResponder $responses): JsonResponse
    {
        return $responses->respond(fn () => $service->sessions($request->user()), 'Failed to retrieve sessions');
    }
}
