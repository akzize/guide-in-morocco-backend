<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TourImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'tour_id',
        'image_url',
        'order_sequence',
        'is_primary'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'order_sequence' => 'integer',
    ];

    /**
     * Get the tour that owns the image
     */
    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }
}
