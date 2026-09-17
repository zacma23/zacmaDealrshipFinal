<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ResellerApiKey extends Model
{
    use HasFactory;

    protected $table = 'reseller_api_keys';

    protected $fillable = [
        'user_id',
        'organization_id',
        'name',
        'api_key_hash',
        'key_prefix',
        'rate_limit_per_minute',
        'ip_whitelist',
        'webhook_url',
        'webhook_secret',
        'is_active',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'ip_whitelist' => 'array',
        'is_active' => 'boolean',
        'rate_limit_per_minute' => 'integer',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public static function generateForUser(User $user, string $name = 'Default API Key'): array
    {
        $rawKey = 'zac_' . Str::random(40);
        $prefix = substr($rawKey, 0, 12) . '...';

        $apiKey = static::create([
            'user_id' => $user->id,
            'organization_id' => $user->organization_id,
            'name' => $name,
            'api_key_hash' => hash('sha256', $rawKey),
            'key_prefix' => $prefix,
            'rate_limit_per_minute' => 120,
            'is_active' => true,
        ]);

        return [
            'model' => $apiKey,
            'plainTextKey' => $rawKey,
        ];
    }

    public static function findByPlainTextKey(string $plainTextKey): ?self
    {
        $hash = hash('sha256', $plainTextKey);
        return static::where('api_key_hash', $hash)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();
    }
}
