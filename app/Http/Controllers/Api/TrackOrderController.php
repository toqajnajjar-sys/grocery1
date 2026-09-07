<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\OperationResponder;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackOrderController extends Controller
{
    public function __invoke(Request $request, OrderService $service, OperationResponder $responses): JsonResponse
    {
        return $responses->respond(fn () => $service->track($request->user()), 'Failed to track order');
    }
}
