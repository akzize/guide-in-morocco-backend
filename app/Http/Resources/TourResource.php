<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TourResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'duration_in_hours' => $this->duration_in_hours,
            'duration_formatted' => $this->duration_formatted,
            'price' => $this->price,
            'max_persons' => $this->max_persons,
            'min_persons' => $this->min_persons,
            'average_rating' => $this->average_rating,
            'total_reviews' => $this->total_reviews,
            'total_bookings' => $this->total_bookings,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relations (loaded conditionally)
            'guide' => $this->whenLoaded('guide'),
            'city' => $this->whenLoaded('city'),
            'tour_type' => $this->whenLoaded('tourType'),
            'difficulty_level' => $this->whenLoaded('difficultyLevel'),
            'currency' => $this->whenLoaded('currency'),
            "primary_image" => $this->whenLoaded('primaryImage'),
            "images" => $this->whenLoaded('images'),
            "gallery_images" => $this->whenLoaded('galleryImages'),

            // Nested relations
            'stops' => $this->whenLoaded('stops'),
            'inclusions' => $this->whenLoaded('inclusions'),

        ];
    }
}
