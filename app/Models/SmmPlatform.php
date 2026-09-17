<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmmPlatform extends Model
{
    use HasFactory;

    protected $table = 'smm_platforms';

    protected $fillable = [
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

    public function categories(): HasMany
    {
        return $this->hasMany(SmmCategory::class, 'smm_platform_id')->orderBy('sort_order');
    }

    public function services(): HasMany
    {
        return $this->hasMany(SmmService::class, 'smm_platform_id');
    }
}
