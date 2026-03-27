<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTourStopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stop_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'duration_in_minutes' => 'nullable|integer|min:1',
            'location_coordinates' => 'nullable|array',
            'photo_url' => 'nullable|string|url',
            'order_sequence' => 'nullable|integer|min:1',
        ];
    }
}
