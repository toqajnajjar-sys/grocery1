<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        return [
            'site_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'site_description' => ['sometimes', 'nullable', 'string'],
            'copyright_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'facebook' => ['sometimes', 'nullable', 'url', 'max:255'],
            'linkedin' => ['sometimes', 'nullable', 'url', 'max:255'],
            'instagram' => ['sometimes', 'nullable', 'url', 'max:255'],
            'twitter' => ['sometimes', 'nullable', 'url', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string'],
            'logo' => ['sometimes', 'nullable', 'image', 'max:2048'],
            'favicon' => ['sometimes', 'nullable', 'image', 'max:2048'],
            'shipping_fee' => ['sometimes', 'numeric', 'min:0'],
            'free_shipping_min_order' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }
}
