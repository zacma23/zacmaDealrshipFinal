<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $table = 'wallet_transactions';

    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_ORDER_CHARGE = 'order_charge';
    public const TYPE_ORDER_REFUND = 'order_refund';
    public const TYPE_REFUND = 'order_refund';
    public const TYPE_MANUAL_CREDIT = 'manual_credit';
    public const TYPE_MANUAL_DEBIT = 'manual_debit';
    public const TYPE_AFFILIATE_COMMISSION = 'affiliate_commission';

    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PENDING = 'pending';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'wallet_id',
        'user_id',
        'organization_id',
        'type',
        'amount',
        'fee',
        'balance_before',
        'balance_after',
        'currency',
        'reference',
        'payment_id',
        'smm_order_id',
        'description',
        'metadata',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'fee' => 'decimal:4',
        'balance_before' => 'decimal:4',
        'balance_after' => 'decimal:4',
        'metadata' => 'array',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function smmOrder(): BelongsTo
    {
        return $this->belongsTo(SmmOrder::class, 'smm_order_id');
    }
}
