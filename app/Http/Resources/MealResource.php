<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MealResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'offer_title' => $this->offer_title,
            'recommendation_reason' => $this->when(
                isset($this->recommendation_reason),
                fn () => $this->recommendation_reason
            ),
            ...$this->getApiPriceAttributes(),
            'has_offer' => $this->hasOffer(),

            'rating' => (float) $this->rating,
            'rating_count' => (int) $this->rating_count,
            'size' => $this->size,
            'brand' => $this->brand,

            'stock_quantity' => (int) $this->stock_quantity,
            'in_stock' => $this->isInStock(),
            'is_featured' => $this->is_featured,
            'is_available' => $this->is_available,
            'sold_count' => $this->sold_count,

            'features' => $this->features,
            'available_date' => $this->available_date,
            'created_at' => $this->created_at,

            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null,

            'subcategory' => $this->subcategory ? [
                'id' => $this->subcategory->id,
                'name' => $this->subcategory->name,
                'slug' => $this->subcategory->slug,
            ] : null,

            'is_favorited' => $this->when(
                isset($this->is_favorited),
                fn () => (bool) $this->is_favorited
            ),
        ];
    }
}

