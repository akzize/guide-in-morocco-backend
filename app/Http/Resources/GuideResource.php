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
            'guide_type' => $this->guide_type,
            'professional_experience' => $this->professional_experience,
            'rating' => $this->rating,
            'total_reviews' => $this->total_reviews,
            'certificate_status' => $this->certificate_status,
            'years_experience' => $this->years_experience,
            'hourly_rate_from' => $this->hourly_rate_from,
            'daily_rate' => $this->daily_rate,
            'cover_image_url' => $this->cover_image_url,
            'popular_flag' => $this->popular_flag,
            'main_city' => $this->whenLoaded('cities', function () {
                return $this->cities->first(function ($city) {
                    return (bool) data_get($city, 'pivot.is_main', false);
                });
            }),

            'user' => $this->whenLoaded('user'),
            'languages' => $this->whenLoaded('languages'),
            'cities' => $this->whenLoaded('cities'),
            'specialties' => $this->whenLoaded('specialties'),
            'availabilities' => $this->whenLoaded('availabilities'),
            'tours' => $this->whenLoaded('tours'),
        ];
    }
}
