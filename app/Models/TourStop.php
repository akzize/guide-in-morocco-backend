<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TourStop extends Model
{
    /** @use HasFactory<\Database\Factories\TourStopFactory> */
    use HasFactory;

 protected $fillable = [
        'tour_id',
        'stop_name',
        'description',
        'order_sequence',
        'duration_in_minutes',
    ];
}
