<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProfileImageRequest extends ProfileRequest
{
    private const SINGLE_IMAGE_MESSAGE = 'Only one profile image is allowed';

    public function rules(): array
    {
        return ['image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048']];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->hasMultipleImages()) {
                $validator->errors()->add('image', self::SINGLE_IMAGE_MESSAGE);
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->hasMultipleImages()) {
            throw new HttpResponseException(response()->json([
                'success' => false, 'message' => self::SINGLE_IMAGE_MESSAGE,
                'errors' => ['image' => [self::SINGLE_IMAGE_MESSAGE]],
            ], 422));
        }
        parent::failedValidation($validator);
    }

    private function hasMultipleImages(): bool
    {
        return count($this->allFiles()) > 1 || is_array($this->file('image'));
    }
}
