<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TourInclusion extends Model
{
    /** @use HasFactory<\Database\Factories\TourInclusionFactory> */
    use HasFactory;
    protected $fillable = [
        'tour_id',
        'inclusion_text',
        'order_sequence',
    ];
}
