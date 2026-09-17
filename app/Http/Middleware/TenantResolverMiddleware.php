<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantResolverMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = null;
        $host = $request->getHost();

        // 1. Check custom domain or subdomain
        // e.g. dealer1.zacmaa.net or customdomain.com
        $tenant = Organization::where('custom_domain', $host)
            ->orWhere('subdomain', $this->extractSubdomain($host))
            ->first();

        // 2. Check header X-Tenant-ID (for APIs)
        if (!$tenant && $request->hasHeader('X-Tenant-ID')) {
            $tenant = Organization::find($request->header('X-Tenant-ID'));
        }

        // 3. Check session or authenticated user's organization
        if (!$tenant) {
            $sessionOrgId = session('active_organization_id');
            if ($sessionOrgId) {
                $tenant = Organization::find($sessionOrgId);
            } elseif ($request->user() && $request->user()->organization_id) {
                $tenant = $request->user()->organization;
            }
        }

        if ($tenant) {
            app()->instance('current_organization', $tenant);
            app()->instance('current_organization_id', $tenant->id);
            view()->share('currentTenant', $tenant);
        }

        $response = $next($request);

        // Expunge any legacy oversized cookies (40-char random session IDs from previous driver)
        foreach ($request->cookies->keys() as $cookieName) {
            if (strlen($cookieName) === 40 && !in_array($cookieName, ['zacma_session', 'XSRF-TOKEN'])) {
                $response->headers->setCookie(
                    new \Symfony\Component\HttpFoundation\Cookie(
                        $cookieName, null, 1, '/', null, true, true, false, 'lax'
                    )
                );
            }
        }

        return $response;
    }

    protected function extractSubdomain(string $host): ?string
    {
        $parts = explode('.', $host);
        if (count($parts) >= 3) {
            return $parts[0];
        }
        return null;
    }
}
