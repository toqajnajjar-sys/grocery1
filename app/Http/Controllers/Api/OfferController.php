<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\OfferIndexRequest;
use App\Http\Requests\Api\ValidateOfferRequest;
use App\Http\Resources\Api\OfferResource;
use App\Services\OfferService;

class OfferController extends Controller
{
    public function __construct(private OfferService $offers) {}

    public function index(OfferIndexRequest $request)
    {
        return OfferResource::collection($this->offers->search($request->validated()));
    }

    public function show(string $code)
    {
        return new OfferResource($this->offers->byCode($code));
    }

    public function validateOffer(ValidateOfferRequest $request)
    {
        $result = $this->offers->validateCode($request->validated());
        if (! isset($result['offer'])) {
            return response()->json($result, 404);
        }
        $result['offer'] = new OfferResource($result['offer']);

        return response()->json($result);
    }
}
