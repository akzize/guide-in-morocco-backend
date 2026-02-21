<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tour extends Model
{
    /** @use HasFactory<\Database\Factories\TourFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    public function guide()
    {
        return $this->belongsTo(Guide::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function tourType()
    {
        return $this->belongsTo(TourType::class);
    }

    public function difficultyLevel()
    {
        return $this->belongsTo(DifficultyLevel::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function stops()
    {
        return $this->hasMany(TourStop::class)->orderBy('order_sequence');
    }

    public function inclusions()
    {
        return $this->hasMany(TourInclusion::class)->orderBy('order_sequence');
    }
}
