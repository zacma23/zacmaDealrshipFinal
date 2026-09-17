<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceSubscriptionLimitsMiddleware
{
    public function handle(Request $request, Closure $next, string $feature = 'general'): Response
    {
        $user = $request->user();

        // Super admins have no subscription restrictions
        if ($user && $user->role === 'SUPER_ADMIN') {
            return $next($request);
        }

        $organization = app()->has('current_organization')
            ? app('current_organization')
            : ($user ? $user->organization : null);

        if (!$organization) {
            return $next($request);
        }

        if ($organization->status === 'suspended') {
            abort(403, 'This organization account is currently suspended. Please renew subscription or contact billing.');
        }

        if ($feature === 'listing' && !$organization->canCreateListing()) {
            abort(403, 'Organization has reached its listing limit under current plan. Please upgrade plan.');
        }

        if ($feature === 'contact' && !$organization->canCreateContact()) {
            abort(403, 'Organization has reached its CRM contact limit under current plan. Please upgrade plan.');
        }

        if ($feature === 'user' && !$organization->canAddUser()) {
            abort(403, 'Organization has reached its staff user limit under current plan. Please upgrade plan.');
        }

        return $next($request);
    }
}
