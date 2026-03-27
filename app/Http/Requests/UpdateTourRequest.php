<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTourRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
        'title' => 'required|string|max:255',
        'description' => 'required|string',
        'city_id' => 'required|exists:cities,id',
        'tour_type_id' => 'required|exists:tour_types,id',
        'difficulty_level_id' => 'required|exists:difficulty_levels,id',
        'duration_in_hours' => 'required|numeric|min:0.5',
        'price' => 'required|numeric|min:0',
        'currency_id' => 'required|exists:currencies,id',
        'max_persons' => 'required|integer|min:1',
        'min_persons' => 'nullable|integer|min:1',
        'status' => 'required|in:draft,published,archived',

        // optional arrays
        'images' => 'nullable|array',
        'images.*.image_url' => 'required|string',

        'stops' => 'nullable|array',
        'stops.*.name' => 'required|string',

        'inclusions' => 'nullable|array',
        'inclusions.*.name' => 'required|string',
    ];
    }
}
