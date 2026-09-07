<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ReviewIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['meal_id' => ['sometimes', 'integer', 'min:1'], 'user_id' => ['sometimes', 'integer', 'min:1'],
            'rating' => ['sometimes', 'integer', 'between:1,5'], 'min_rating' => ['sometimes', 'numeric', 'between:1,5'],
            'approved_only' => ['sometimes', 'in:0,1,true,false,on,off,yes,no'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100']];
    }
}
