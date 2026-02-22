<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuideDocument extends Model
{
    /** @use HasFactory<\Database\Factories\GuideDocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'guide_id',
        'document_type',
        'file_url',
        'file_size',
        'verification_status',
        'verified_by',
        'verified_at',
        'rejection_reason',
        'expiry_date',
    ];
}
