<?php

namespace App\Models\Scopes;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // If running in CLI/console (e.g. migration, seeder) and not in unit tests, do not restrict unless tenant is bound
        if (app()->runningInConsole() && !app()->runningUnitTests() && !app()->has('current_organization_id')) {
            return;
        }

        // Super admins have global visibility unless viewing a specific tenant's portal
        $user = Auth::user();
        if ($user && $user->role === 'SUPER_ADMIN' && !app()->has('current_organization_id')) {
            return;
        }

        $organizationId = null;
        if (app()->has('current_organization_id')) {
            $organizationId = app('current_organization_id');
        } elseif ($user && $user->organization_id) {
            $organizationId = $user->organization_id;
        }

        if ($organizationId) {
            $builder->where($model->qualifyColumn('organization_id'), $organizationId);
        } else {
            // Unauthenticated public request on main domain or no tenant set:
            // Prevent accidental exposure of tenant data by default on tenant-owned models
            // Public listing searches explicitly use withoutGlobalScope(TenantScope::class)
            $builder->where($model->qualifyColumn('organization_id'), -1);
        }
    }
}
