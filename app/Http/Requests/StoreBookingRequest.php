<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
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
        'tour_id' => 'required|exists:tours,id',
        'guide_id' => 'required|exists:guides,id',
        'booking_date' => 'required|date',
        'number_of_persons' => 'required|integer|min:1',
        'total_price' => 'required|numeric',
        'currency_id' => 'required|exists:currencies,id',
        'special_requests' => 'nullable|string',
    ];
}
}
