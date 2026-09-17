<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'key',
        'value',
        'type',
        'group',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public static function get(string $key, $default = null, ?int $organizationId = null)
    {
        $setting = static::where('organization_id', $organizationId)
            ->where('key', $key)
            ->first();

        // Fallback to platform-wide setting if tenant setting not found
        if (!$setting && $organizationId !== null) {
            $setting = static::whereNull('organization_id')
                ->where('key', $key)
                ->first();
        }

        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => (bool) $setting->value,
            'json' => json_decode($setting->value, true),
            'integer' => (int) $setting->value,
            default => $setting->value,
        };
    }

    public static function set(string $key, $value, string $group = 'general', ?int $organizationId = null, string $type = 'string'): self
    {
        $stringValue = is_array($value) ? json_encode($value) : (string) $value;

        return static::updateOrCreate(
            ['organization_id' => $organizationId, 'key' => $key],
            ['value' => $stringValue, 'group' => $group, 'type' => $type]
        );
    }
}
