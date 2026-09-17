<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeatureFlag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_enabled',
        'allowed_organization_ids',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'allowed_organization_ids' => 'array',
    ];

    public static function isEnabled(string $name, ?int $orgId = null): bool
    {
        $flag = static::where('name', $name)->first();
        if (!$flag || !$flag->is_enabled) {
            return false;
        }

        if (empty($flag->allowed_organization_ids)) {
            return true; // Enabled globally
        }

        return $orgId !== null && in_array($orgId, $flag->allowed_organization_ids);
    }
}
