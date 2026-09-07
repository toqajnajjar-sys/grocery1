<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['meal_id' => ['required', 'integer', 'exists:meals,id'], 'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'images' => ['sometimes', 'nullable', 'array'], 'images.*' => ['string', 'max:2048']];
    }
}
