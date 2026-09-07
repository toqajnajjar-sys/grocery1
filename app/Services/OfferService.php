<?php

namespace App\Services;

use App\Models\Offer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class OfferService
{
    public function __construct(private QueryFilterPipeline $filters) {}

    public function search(array $input): LengthAwarePaginator
    {
        return $this->filters->apply('offers', Offer::active(), $input)
            ->orderBy($input['order_by'] ?? 'created_at', $input['order_direction'] ?? 'desc')
            ->paginate($input['per_page'] ?? 15);
    }

    public function featured(): Collection
    {
        return Offer::featured()->orderBy('created_at', 'desc')->limit(5)->get();
    }

    public function byCode(string $code): Offer
    {
        return Offer::where('code', $code)->firstOrFail();
    }

    public function validateCode(array $input): array
    {
        $offer = Offer::where('code', $input['code'])->first();
        if (! $offer) {
            return ['valid' => false, 'message' => 'Invalid offer code'];
        }
        $isValid = $offer->isValid();
        $canApply = true;
        $message = 'Offer is valid';
        if ($isValid && array_key_exists('amount', $input)) {
            $canApply = $offer->canApplyToAmount($input['amount'] ?? 0);
            if (! $canApply) {
                $message = 'Minimum purchase required: $'.$offer->minimum_purchase;
            }
        }

        return [
            'valid' => $isValid && $canApply, 'offer' => $offer,
            'discount_amount' => $isValid && $canApply ? $offer->calculateDiscount($input['amount'] ?? 0) : 0,
            'message' => $message,
        ];
    }
}
