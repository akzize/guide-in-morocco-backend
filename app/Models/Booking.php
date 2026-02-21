<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    /** @use HasFactory<\Database\Factories\BookingFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function guide()
    {
        return $this->belongsTo(Guide::class);
    }

    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }
}
