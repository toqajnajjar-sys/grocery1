<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Responses\OperationResponder;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private OrderService $service, private OperationResponder $responses) {}

    public function show(Request $request, string $id): JsonResponse
    {
        return $this->responses->respond(fn () => $this->service->show($request->user(), $id), 'Failed to retrieve order');
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        return $this->responses->respond(fn () => $this->service->store($request->user(), $request->validated()), 'Failed to create order', 201);
    }

    public function index(Request $request): JsonResponse
    {
        return $this->responses->respond(fn () => $this->service->index($request->user()), 'Failed to retrieve orders');
    }

    public function track(Request $request): JsonResponse
    {
        return $this->responses->respond(fn () => $this->service->track($request->user()), 'Failed to track order');
    }
}
