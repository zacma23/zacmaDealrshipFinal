<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkCustomerImport extends Model
{
    use HasFactory, BelongsToOrganization;

    protected $table = 'bulk_customer_imports';

    protected $fillable = [
        'user_id',
        'organization_id',
        'file_name',
        'total_rows',
        'valid_rows',
        'invalid_rows',
        'duplicate_rows',
        'imported_count',
        'status',
        'error_report',
    ];

    protected $casts = [
        'error_report' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
