<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TourStopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tour_id' => $this->tour_id,
            'order_sequence' => $this->order_sequence,
            'stop_name' => $this->stop_name,
            'description' => $this->description,
            'duration_in_minutes' => $this->duration_in_minutes,
            'location_coordinates' => $this->location_coordinates ? json_decode($this->location_coordinates, true) : null,
            'photo_url' => $this->photo_url,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
