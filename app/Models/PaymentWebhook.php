<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'event_type',
        'payload',
        'is_processed',
        'error_log',
    ];

    protected $casts = [
        'payload' => 'array',
        'is_processed' => 'boolean',
    ];
}
