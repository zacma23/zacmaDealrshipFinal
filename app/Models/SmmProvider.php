<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmmProvider extends Model
{
    use HasFactory;

    protected $table = 'smm_providers';

    protected $fillable = [
        'name',
        'api_url',
        'api_key',
        'adapter_type',
        'status',
        'balance',
        'currency',
        'priority',
        'fallback_provider_id',
        'last_sync_at',
        'health_status',
        'error_log',
    ];

    protected $casts = [
        'balance' => 'decimal:4',
        'last_sync_at' => 'datetime',
        'priority' => 'integer',
    ];

    protected $hidden = [
        'api_key',
    ];

    public function services(): HasMany
    {
        return $this->hasMany(SmmService::class, 'smm_provider_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(SmmOrder::class, 'smm_provider_id');
    }

    public function fallbackProvider(): BelongsTo
    {
        return $this->belongsTo(SmmProvider::class, 'fallback_provider_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
