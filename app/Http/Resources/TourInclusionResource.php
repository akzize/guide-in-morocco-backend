<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TourInclusionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tour_id' => $this->tour_id,
            'inclusion_text' => $this->inclusion_text,
            'order_sequence' => $this->order_sequence,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
