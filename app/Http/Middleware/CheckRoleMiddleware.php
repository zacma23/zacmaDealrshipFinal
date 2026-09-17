<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->is_active) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Your account has been deactivated. Please contact support.');
        }

        // Super Admin (and active impersonator) bypasses role checks for all management portals
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user is currently being impersonated by a Super Admin
        if ($request->session()->has('impersonator_id')) {
            $impersonator = \App\Models\User::find($request->session()->get('impersonator_id'));
            if ($impersonator && $impersonator->isSuperAdmin()) {
                return $next($request);
            }
        }

        if (empty($roles)) {
            return $next($request);
        }

        // Robustly parse comma-separated role arguments
        $allowed = [];
        foreach ($roles as $r) {
            foreach (explode(',', (string) $r) as $sub) {
                $trimmed = strtoupper(trim($sub));
                if ($trimmed !== '') {
                    $allowed[] = $trimmed;
                    // Also allow slug variants
                    $allowed[] = str_replace([' ', '-'], '_', $trimmed);
                }
            }
        }

        $userRoleNormalized = strtoupper(str_replace([' ', '-'], '_', (string)$user->role));

        if (in_array($userRoleNormalized, $allowed, true) || in_array($user->role, $allowed, true)) {
            return $next($request);
        }

        abort(403, "Unauthorized access to this portal. You are currently signed in as {$user->name} ({$user->role}). Super Admin privileges (e.g. admin@zacma.com) are required.");
    }
}
