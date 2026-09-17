<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmmCategory extends Model
{
    use HasFactory;

    protected $table = 'smm_categories';

    protected $fillable = [
        'smm_platform_id',
        'name',
        'slug',
        'icon',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function platform(): BelongsTo
    {
        return $this->belongsTo(SmmPlatform::class, 'smm_platform_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(SmmService::class, 'smm_category_id')->orderBy('sort_order');
    }
}
