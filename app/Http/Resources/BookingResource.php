<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_date' => $this->booking_date,
            'number_of_persons' => $this->number_of_persons,
            'total_price' => $this->total_price,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'special_requests' => $this->special_requests,
            'created_at' => $this->created_at,

            'client' => $this->whenLoaded('client'),
            'guide' => $this->whenLoaded('guide'),
            'tour' => $this->whenLoaded('tour'),
            'currency' => $this->whenLoaded('currency'),
            'review' => $this->whenLoaded('review'),
        ];
    }
}
