<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmmBulkOrder extends Model
{
    use HasFactory, BelongsToOrganization;

    protected $table = 'smm_bulk_orders';

    protected $fillable = [
        'user_id',
        'organization_id',
        'total_rows',
        'valid_rows',
        'invalid_rows',
        'successful_orders',
        'failed_orders',
        'total_cost',
        'status',
        'report_log',
    ];

    protected $casts = [
        'total_cost' => 'decimal:4',
        'report_log' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
