<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTourStopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stops' => 'sometimes|array',
            'stops.*.stop_name' => 'required|string|max:255',
            'stops.*.description' => 'nullable|string',
            'stops.*.duration_in_minutes' => 'nullable|integer|min:1',
            'stops.*.location_coordinates' => 'nullable|array',
            'stops.*.photo_url' => 'nullable|string|url',
            'stops.*.order_sequence' => 'nullable|integer|min:1',

            // Single stop fields
            'stop_name' => 'required_without:stops|string|max:255',
            'description' => 'nullable|string',
            'duration_in_minutes' => 'nullable|integer|min:1',
            'location_coordinates' => 'nullable|array',
            'photo_url' => 'nullable|string|url',
            'order_sequence' => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'stop_name.required_without' => 'The stop name is required when not creating multiple stops.',
        ];
    }
}
