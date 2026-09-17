<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmLead extends Model
{
    use HasFactory;

    public const STATUS_NEW = 'New';
    public const STATUS_CONTACTED = 'Contacted';
    public const STATUS_CLOSED = 'Closed';

    public const STAGES = [
        self::STATUS_NEW,
        self::STATUS_CONTACTED,
        self::STATUS_CLOSED,
    ];

    protected $fillable = [
        'inquiry_id',
        'user_id',
        'buyer_id',
        'listing_id',
        'buyer_name',
        'buyer_email',
        'buyer_phone',
        'message',
        'status',
    ];

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id'); // listing owner / seller
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}

