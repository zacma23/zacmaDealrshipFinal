<?php

namespace App\Policies;

use App\Models\CrmLead;
use App\Models\User;

class CrmLeadPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CrmLead $lead): bool
    {
        return $user->id === $lead->user_id || $user->isSuperAdmin();
    }

    public function update(User $user, CrmLead $lead): bool
    {
        return $user->id === $lead->user_id || $user->isSuperAdmin();
    }

    public function delete(User $user, CrmLead $lead): bool
    {
        return $user->id === $lead->user_id || $user->isSuperAdmin();
    }
}

