<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Listing extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_VEHICLE = 'vehicle';
    public const TYPE_REAL_ESTATE = 'real_estate';
    public const TYPE_APARTMENT = 'apartment';
    public const TYPE_PRODUCT = 'product';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SOLD = 'sold';
    public const STATUS_RENTED = 'rented';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'user_id',
        'category_id',
        'type',
        'title',
        'slug',
        'description',
        'price',
        'currency',
        'city',
        'address',
        'status',
        'rejection_reason',
        'year',
        'bedrooms',
        'listing_attributes',
        'views_count',
        'featured',
        'published_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'year' => 'integer',
        'bedrooms' => 'integer',
        'listing_attributes' => 'array',
        'views_count' => 'integer',
        'featured' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($listing) {
            if (empty($listing->slug)) {
                $listing->slug = Str::slug($listing->title) . '-' . Str::random(6);
            }
            if (empty($listing->currency)) {
                $listing->currency = 'ETB';
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class)->orderBy('order')->orderBy('id');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ListingImage::class)->where('is_primary', true);
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function crmLeads(): HasMany
    {
        return $this->hasMany(CrmLead::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                  ->orWhere('description', 'like', $search)
                  ->orWhere('city', 'like', $search);
            });
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        if (isset($filters['min_price']) && is_numeric($filters['min_price'])) {
            $query->where('price', '>=', (float) $filters['min_price']);
        }

        if (isset($filters['max_price']) && is_numeric($filters['max_price'])) {
            $query->where('price', '<=', (float) $filters['max_price']);
        }

        // Vehicle filters
        if (!empty($filters['year'])) {
            $query->where('year', (int) $filters['year']);
        }
        if (!empty($filters['min_year'])) {
            $query->where('year', '>=', (int) $filters['min_year']);
        }
        if (!empty($filters['brand'])) {
            $query->where('listing_attributes->brand', $filters['brand']);
        }
        if (!empty($filters['transmission'])) {
            $query->where('listing_attributes->transmission', $filters['transmission']);
        }
        if (!empty($filters['fuel_type'])) {
            $query->where('listing_attributes->fuel_type', $filters['fuel_type']);
        }

        // Real Estate & Apartment filters
        if (!empty($filters['bedrooms'])) {
            $query->where('bedrooms', '>=', (int) $filters['bedrooms']);
        }
        if (!empty($filters['property_purpose'])) {
            $query->where('listing_attributes->property_purpose', $filters['property_purpose']);
        }
        if (!empty($filters['furnished'])) {
            $query->where('listing_attributes->furnished', $filters['furnished']);
        }
        if (!empty($filters['rent_period'])) {
            $query->where('listing_attributes->rent_period', $filters['rent_period']);
        }

        return $query;
    }

    public function scopeSort(Builder $query, ?string $sort): Builder
    {
        return match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'oldest' => $query->orderBy('created_at', 'asc'),
            default => $query->orderByDesc('created_at'),
        };
    }

    public function getAttributeValueByKey(string $key, mixed $default = null): mixed
    {
        return $this->listing_attributes[$key] ?? $default;
    }

    public function getPrimaryImageUrl(): string
    {
        $primary = $this->images->firstWhere('is_primary', true) ?: $this->images->first();
        if ($primary && $primary->image_path) {
            return str_starts_with($primary->image_path, 'http')
                ? $primary->image_path
                : asset('storage/' . $primary->image_path);
        }

        // Default placeholder based on type
        return match ($this->type) {
            self::TYPE_VEHICLE => 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=800&auto=format&fit=crop&q=60',
            self::TYPE_REAL_ESTATE => 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=800&auto=format&fit=crop&q=60',
            self::TYPE_APARTMENT => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=800&auto=format&fit=crop&q=60',
            self::TYPE_PRODUCT => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800&auto=format&fit=crop&q=60',
            default => 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=800&auto=format&fit=crop&q=60',
        };
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('is_approved', true);
    }
}

