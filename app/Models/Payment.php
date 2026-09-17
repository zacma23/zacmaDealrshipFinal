<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    public const PROVIDER_SANTIMPAY = 'santimpay';
    public const PROVIDER_TELEBIRR = 'telebirr';
    public const PROVIDER_CHAPA = 'chapa';
    public const PROVIDER_PAYPAL = 'paypal';
    public const PROVIDER_CARD = 'card';
    public const PROVIDER_CRYPTO = 'crypto';
    public const PROVIDER_CASH = 'cash';

    public const STATUS_PENDING = 'pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'organization_id',
        'order_id',
        'user_id',
        'subscription_id',
        'plan_id',
        'provider',
        'amount',
        'currency',
        'transaction_reference',
        'provider_reference',
        'status',
        'idempotency_key',
        'request_payload',
        'response_payload',
        'verified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function isVerified(): bool
    {
        return in_array($this->status, [self::STATUS_VERIFIED, self::STATUS_COMPLETED], true);
    }
}
