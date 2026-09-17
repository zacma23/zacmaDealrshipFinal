<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'price',
        'price_quarterly',
        'price_yearly',
        'currency',
        'listing_limit',
        'billing_period',
        'features',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'price_quarterly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'listing_limit' => 'integer',
        'features' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($plan) {
            if (empty($plan->slug)) {
                $plan->slug = Str::slug($plan->name);
            }
        });
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }
}

