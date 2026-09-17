<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    protected $table = 'wallets';

    protected $fillable = [
        'user_id',
        'organization_id',
        'balance',
        'currency',
        'total_deposited',
        'total_spent',
        'total_refunded',
        'status',
    ];

    protected $casts = [
        'balance' => 'decimal:4',
        'total_deposited' => 'decimal:4',
        'total_spent' => 'decimal:4',
        'total_refunded' => 'decimal:4',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'wallet_id')->latest();
    }

    public function isFrozen(): bool
    {
        return $this->status === 'frozen';
    }

    public function hasSufficientBalance(float $amount): bool
    {
        return !$this->isFrozen() && (float)$this->balance >= $amount;
    }
}
