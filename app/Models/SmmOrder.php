<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmmOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'smm_orders';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_CANCELED = 'cancelled';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'order_number',
        'organization_id',
        'user_id',
        'smm_service_id',
        'smm_provider_id',
        'provider_order_id',
        'target',
        'quantity',
        'charge',
        'cost',
        'profit',
        'currency',
        'start_count',
        'remains',
        'status',
        'is_drip_feed',
        'drip_runs',
        'drip_interval',
        'drip_delivered',
        'has_refill',
        'refill_status',
        'error_message',
        'is_api_order',
        'reseller_api_key_id',
        'metadata',
    ];

    protected $casts = [
        'charge' => 'decimal:4',
        'cost' => 'decimal:4',
        'profit' => 'decimal:4',
        'quantity' => 'integer',
        'start_count' => 'integer',
        'remains' => 'integer',
        'is_drip_feed' => 'boolean',
        'has_refill' => 'boolean',
        'is_api_order' => 'boolean',
        'metadata' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(SmmService::class, 'smm_service_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(SmmProvider::class, 'smm_provider_id');
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ResellerApiKey::class, 'reseller_api_key_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'smm_order_id');
    }

    public function canRefill(): bool
    {
        return $this->has_refill 
            && in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_PARTIAL])
            && in_array($this->refill_status, ['none', 'rejected']);
    }

    public function canCancel(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }
}
