<?php

namespace App\Policies;

use App\Models\Listing;
use App\Models\User;

class ListingPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Listing $listing): bool
    {
        if ($listing->status === Listing::STATUS_PUBLISHED) {
            return true;
        }

        if (!$user) {
            return false;
        }

        return $user->id === $listing->user_id || $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->canCreateListing();
    }

    public function update(User $user, Listing $listing): bool
    {
        return $user->id === $listing->user_id || $user->isSuperAdmin();
    }

    public function delete(User $user, Listing $listing): bool
    {
        return $user->id === $listing->user_id || $user->isSuperAdmin();
    }

    public function approve(User $user, Listing $listing): bool
    {
        return $user->isSuperAdmin();
    }

    public function reject(User $user, Listing $listing): bool
    {
        return $user->isSuperAdmin();
    }
}

