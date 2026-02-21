<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
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
            'rating' => $this->rating,
            'review_text' => $this->review_text,
            'helpful_count' => $this->helpful_count,
            'status' => $this->status,
            'created_at' => $this->created_at,

            'client' => $this->whenLoaded('client'),
            'guide' => $this->whenLoaded('guide'),
            'tour' => $this->whenLoaded('tour'),
            'booking' => $this->whenLoaded('booking'),
        ];
    }
}
