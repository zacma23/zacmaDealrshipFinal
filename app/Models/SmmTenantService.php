<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmmTenantService extends Model
{
    use HasFactory, BelongsToOrganization;

    protected $table = 'smm_tenant_services';

    protected $fillable = [
        'organization_id',
        'smm_service_id',
        'custom_name',
        'custom_description',
        'custom_price_per_k',
        'min_quantity',
        'max_quantity',
        'is_visible',
    ];

    protected $casts = [
        'custom_price_per_k' => 'decimal:4',
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'is_visible' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(SmmService::class, 'smm_service_id');
    }
}
