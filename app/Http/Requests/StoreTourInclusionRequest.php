<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTourInclusionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inclusions' => 'sometimes|array',
            'inclusions.*.inclusion_text' => 'required|string|max:255',
            'inclusions.*.order_sequence' => 'nullable|integer|min:1',

            // Single inclusion fields
            'inclusion_text' => 'required_without:inclusions|string|max:255',
            'order_sequence' => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'inclusion_text.required_without' => 'The inclusion text is required when not creating multiple inclusions.',
        ];
    }
}
