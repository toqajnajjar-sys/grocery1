<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\OfferResource;
use App\Services\OfferService;

class FeaturedOfferController extends Controller
{
    public function __invoke(OfferService $offers)
    {
        return OfferResource::collection($offers->featured());
    }
}
