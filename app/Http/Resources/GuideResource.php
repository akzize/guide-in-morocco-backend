<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuideResource extends JsonResource
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
            'location' => $this->location,
            'bio' => $this->bio,
            'rating' => $this->rating,
            'total_reviews' => $this->total_reviews,
            'certificate_status' => $this->certificate_status,
            'years_experience' => $this->years_experience,
            'hourly_rate_from' => $this->hourly_rate_from,
            'cover_image_url' => $this->cover_image_url,
            'popular_flag' => $this->popular_flag,
            
            'user' => $this->whenLoaded('user'),
            'languages' => $this->whenLoaded('languages'),
            'specialties' => $this->whenLoaded('specialties'),
            'availabilities' => $this->whenLoaded('availabilities'),
            'tours' => $this->whenLoaded('tours'),
        ];
    }
}
