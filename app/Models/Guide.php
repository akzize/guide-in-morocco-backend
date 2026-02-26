<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guide extends Model
{
    /** @use HasFactory<\Database\Factories\GuideFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function languages()
    {
        return $this->belongsToMany(Language::class, 'guide_languages')
                    ->withPivot(['proficiency_level', 'is_principal'])->withTimestamps();
    }

    public function cities()
    {
        return $this->belongsToMany(City::class, 'guide_cities')
                    ->withPivot('is_main')->withTimestamps();
    }

    public function specialties()
    {
        return $this->belongsToMany(Specialty::class, 'guide_specialties')
                    ->withPivot('years_of_specialty')->withTimestamps();
    }

    public function availabilities()
    {
        return $this->hasMany(GuideAvailability::class);
    }

    public function tours()
    {
        return $this->hasMany(Tour::class);
    }
}
