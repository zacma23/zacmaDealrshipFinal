<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    public const ROLE_SUPER_ADMIN = 'SUPER_ADMIN';
    public const ROLE_USER = 'USER';
    public const ROLE_ORGANIZATION_ADMIN = 'ORGANIZATION_ADMIN';
    public const ROLE_MANAGER = 'MANAGER';
    public const ROLE_SALES_AGENT = 'SALES_AGENT';
    public const ROLE_STAFF = 'STAFF';
    public const ROLE_SELLER = 'SELLER';
    public const ROLE_RESELLER = 'RESELLER';
    public const ROLE_CUSTOMER = 'CUSTOMER';

    protected $fillable = [
        'organization_id',
        'name',
        'username',
        'email',
        'phone',
        'password',
        'role',
        'avatar',
        'is_active',
        'email_verified_at',
        'phone_verified_at',
        'otp_code',
        'otp_expires_at',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    protected static function booted()
    {
        static::created(function ($user) {
            if (empty($user->username)) {
                $base = Str::slug($user->name ?: 'user');
                $user->username = $base . '-' . $user->id;
                $user->saveQuietly();
            }

            $user->profile()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'city' => 'Addis Ababa',
                ]
            );

            // Default role assignment
            if ($user->isSuperAdmin()) {
                $user->assignRole(Role::SUPER_ADMIN);
            } else {
                $user->assignRole(Role::USER);
            }
        });
    }

    // ----------------------------------------------------
    // Marketplace & MVP Relations
    // ----------------------------------------------------

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function crmLeads(): HasMany
    {
        return $this->hasMany(CrmLead::class, 'user_id');
    }

    public function inquiriesSent(): HasMany
    {
        return $this->hasMany(Inquiry::class, 'user_id');
    }

    public function inquiriesReceived(): HasMany
    {
        return $this->hasMany(Inquiry::class, 'seller_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('ends_at', '>', now())
            ->latestOfMany();
    }

    // ----------------------------------------------------
    // Existing Platform Relations
    // ----------------------------------------------------

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function smmOrders(): HasMany
    {
        return $this->hasMany(SmmOrder::class);
    }

    public function resellerApiKeys(): HasMany
    {
        return $this->hasMany(ResellerApiKey::class);
    }

    public function smmTickets(): HasMany
    {
        return $this->hasMany(SmmTicket::class);
    }

    public function isReseller(): bool
    {
        return $this->role === self::ROLE_RESELLER || $this->isOrgAdmin();
    }

    public function getOrCreateWallet(): Wallet
    {
        return $this->wallet()->firstOrCreate(
            ['user_id' => $this->id],
            [
                'organization_id' => $this->organization_id,
                'balance' => 0.0000,
                'currency' => 'USD',
                'status' => 'active',
            ]
        );
    }

    // ----------------------------------------------------
    // Role & Quota Helpers
    // ----------------------------------------------------

    public function isSuperAdmin(): bool
    {
        if (in_array(strtoupper(str_replace([' ', '-', '_'], '', (string)$this->role)), ['SUPERADMIN', 'ADMIN'], true)) {
            return true;
        }

        if (strtolower((string)$this->email) === 'admin@zacma.com') {
            return true;
        }

        if (session()->has('impersonator_id')) {
            $impersonator = static::find(session('impersonator_id'));
            if ($impersonator && in_array(strtoupper(str_replace([' ', '-', '_'], '', (string)$impersonator->role)), ['SUPERADMIN', 'ADMIN'], true)) {
                return true;
            }
        }

        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('name', Role::SUPER_ADMIN);
        }

        return $this->roles()->where('name', Role::SUPER_ADMIN)->exists();
    }

    public function assignRole(string $roleName): self
    {
        $role = Role::firstOrCreate(['name' => $roleName], [
            'display_name' => ucfirst(str_replace('_', ' ', $roleName)),
        ]);
        $this->roles()->syncWithoutDetaching([$role->id]);
        return $this;
    }

    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            foreach ($roles as $r) {
                if ($this->hasRole($r)) {
                    return true;
                }
            }
            return false;
        }

        $normalized = strtoupper(str_replace([' ', '-', '_'], '', (string)$roles));
        $myRole = strtoupper(str_replace([' ', '-', '_'], '', (string)$this->role));

        if ($myRole === $normalized) {
            return true;
        }

        if ($this->relationLoaded('roles')) {
            return $this->roles->contains(function ($item) use ($roles, $normalized) {
                return $item->name === $roles || strtoupper(str_replace([' ', '-', '_'], '', $item->name)) === $normalized;
            });
        }

        return $this->roles()->where('name', $roles)->exists();
    }

    public function getListingLimit(): int
    {
        $activeSub = $this->activeSubscription;
        if ($activeSub && $activeSub->plan) {
            return (int) $activeSub->plan->listing_limit;
        }

        $basic = SubscriptionPlan::where('slug', 'basic')->first();
        return $basic ? (int) $basic->listing_limit : 20;
    }

    public function getActiveListingCount(): int
    {
        return $this->listings()
            ->whereIn('status', [
                Listing::STATUS_DRAFT,
                Listing::STATUS_PENDING,
                Listing::STATUS_PUBLISHED,
            ])
            ->count();
    }

    public function canCreateListing(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->getActiveListingCount() < $this->getListingLimit();
    }

    public function isOrgAdmin(): bool
    {
        return $this->role === self::ROLE_ORGANIZATION_ADMIN;
    }

    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    public function isSalesAgent(): bool
    {
        return $this->role === self::ROLE_SALES_AGENT;
    }

    public function isStaff(): bool
    {
        return in_array($this->role, [
            self::ROLE_ORGANIZATION_ADMIN,
            self::ROLE_MANAGER,
            self::ROLE_SALES_AGENT,
            self::ROLE_STAFF
        ]);
    }

    public function isSeller(): bool
    {
        return $this->role === self::ROLE_SELLER;
    }

    public function isCustomer(): bool
    {
        return $this->role === self::ROLE_CUSTOMER;
    }

    public function canAccessTenant(?int $orgId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        return (int) $this->organization_id === (int) $orgId;
    }

    public function getDashboardUrl(): string
    {
        if ($this->isSuperAdmin()) {
            return route('super-admin.smm.dashboard');
        }
        if ($this->role === self::ROLE_RESELLER || $this->isOrgAdmin()) {
            return route('reseller.dashboard');
        }
        return route('customer.smm.dashboard');
    }

    public function getAvatarUrl(): string
    {
        if ($this->profile && $this->profile->photo) {
            return $this->profile->photo_url;
        }
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        $name = urlencode($this->name);
        return "https://ui-avatars.com/api/?name={$name}&color=FFFFFF&background=10B981&bold=true";
    }
}
