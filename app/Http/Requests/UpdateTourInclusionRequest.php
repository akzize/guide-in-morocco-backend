<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTourInclusionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inclusion_text' => 'sometimes|string|max:255',
            'order_sequence' => 'nullable|integer|min:1',
        ];
    }
}
