<?php

namespace App\Policies;

use App\Models\Inquiry;
use App\Models\User;

class InquiryPolicy
{
    public function view(User $user, Inquiry $inquiry): bool
    {
        return $user->id === $inquiry->seller_id || $user->id === $inquiry->user_id || $user->isSuperAdmin();
    }
}

