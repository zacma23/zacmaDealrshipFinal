<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmmService extends Model
{
    use HasFactory;

    protected $table = 'smm_services';

    protected $fillable = [
        'smm_platform_id',
        'smm_category_id',
        'smm_provider_id',
        'provider_service_id',
        'name',
        'service_type',
        'description',
        'cost_per_k',
        'customer_price_per_k',
        'reseller_price_per_k',
        'min_quantity',
        'max_quantity',
        'has_refill',
        'refill_days',
        'has_cancel',
        'start_time',
        'completion_time',
        'quality_level',
        'geo_targeting',
        'dripfeed_supported',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'cost_per_k' => 'decimal:4',
        'customer_price_per_k' => 'decimal:4',
        'reseller_price_per_k' => 'decimal:4',
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'has_refill' => 'boolean',
        'refill_days' => 'integer',
        'has_cancel' => 'boolean',
        'dripfeed_supported' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function platform(): BelongsTo
    {
        return $this->belongsTo(SmmPlatform::class, 'smm_platform_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(SmmCategory::class, 'smm_category_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(SmmProvider::class, 'smm_provider_id');
    }

    public function tenantOverrides(): HasMany
    {
        return $this->hasMany(SmmTenantService::class, 'smm_service_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(SmmOrder::class, 'smm_service_id');
    }

    /**
     * Resolve effective price per 1k based on user role and active tenant/child-panel
     */
    public function getEffectiveRate(?User $user = null, ?Organization $tenant = null): float
    {
        $tenant = $tenant ?? $user?->organization;

        // 1. Reseller / Staff tier wholesale pricing
        if ($user && ($user->role === 'RESELLER' || (method_exists($user, 'isReseller') && $user->isReseller()))) {
            return (float)$this->reseller_price_per_k;
        }

        // 2. Check if child panel has an override or default markup
        if ($tenant) {
            $override = $this->tenantOverrides()->where('organization_id', $tenant->id)->first();
            if ($override && $override->custom_price_per_k !== null) {
                return (float)$override->custom_price_per_k;
            }
            // Child panel default markup
            if ($tenant->default_markup > 0) {
                $base = (float)$this->customer_price_per_k;
                if ($tenant->markup_type === 'percentage') {
                    return round($base * (1 + ($tenant->default_markup / 100)), 4);
                }
                return round($base + (float)$tenant->default_markup, 4);
            }
        }

        // 3. Customer default
        return (float)$this->customer_price_per_k;
    }

    /**
     * Calculate total charge for given quantity
     */
    public function calculateCharge(int $quantity, ?User $user = null, ?Organization $tenant = null): float
    {
        $ratePerK = $this->getEffectiveRate($user, $tenant);
        return round(($quantity / 1000) * $ratePerK, 4);
    }
}
