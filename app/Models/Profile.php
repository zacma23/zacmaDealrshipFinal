<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'city',
        'bio',
        'photo',
        'is_verified',
        'account_type',
        'business_name',
        'license_number',
        'tin_number',
        'address',
        'website',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPhotoUrlAttribute(): string
    {
        if ($this->photo) {
            return str_starts_with($this->photo, 'http')
                ? $this->photo
                : asset('storage/' . $this->photo);
        }

        $name = urlencode($this->name ?: 'User');
        return "https://ui-avatars.com/api/?name={$name}&background=10b981&color=ffffff&size=128";
    }
}

