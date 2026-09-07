<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class OfferIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'nullable', 'string'],
            'min_purchase' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'featured' => ['sometimes', 'in:0,1,true,false,on,off,yes,no'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'order_by' => ['sometimes', 'in:id,title,code,type,discount_value,minimum_purchase,start_date,end_date,usage_limit,used_count,is_active,is_featured,created_at,updated_at'],
            'order_direction' => ['sometimes', 'in:asc,desc'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
