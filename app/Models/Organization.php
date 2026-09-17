<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'subdomain',
        'custom_domain',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'currency',
        'logo',
        'favicon',
        'branding_colors',
        'status',
        'brand_name',
        'theme_config',
        'contact_details',
        'terms_content',
        'privacy_content',
        'markup_type',
        'default_markup',
        'allow_public_registration',
        'custom_domain_status',
    ];

    protected $casts = [
        'branding_colors' => 'array',
        'theme_config' => 'array',
        'contact_details' => 'array',
        'allow_public_registration' => 'boolean',
        'default_markup' => 'decimal:2',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function smmOrders(): HasMany
    {
        return $this->hasMany(SmmOrder::class);
    }

    public function smmTenantServices(): HasMany
    {
        return $this->hasMany(SmmTenantService::class);
    }

    public function smmTickets(): HasMany
    {
        return $this->hasMany(SmmTicket::class);
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getPrimaryColor(): string
    {
        return $this->theme_config['primary_color'] ?? '#2563EB';
    }
}
