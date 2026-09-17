<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect('/');
        }
        return view('app');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => 'required|string', // can be email or phone
            'password' => 'required|string',
        ]);

        $loginField = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $user = User::where($loginField, $credentials['login'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            AuditLog::log('auth.failed', null, null, ['login' => $credentials['login']], null, null);
            return back()->withInput()->with('error', 'Invalid login credentials.');
        }

        if (!$user->is_active) {
            return back()->withInput()->with('error', 'Your account is deactivated. Please contact support.');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // Update login stats
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        AuditLog::log('auth.login', $user, null, ['ip' => $request->ip()], $user->organization_id, $user->id);

        return redirect()->intended('/');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect('/');
        }
        return view('app');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'nullable|string|in:CUSTOMER,SELLER',
            'organization_id' => 'nullable|exists:organizations,id',
        ]);

        try {
            // Default tenant to active organization if on subdomain or first organization
            $orgId = app()->has('current_organization_id')
                ? app('current_organization_id')
                : ($validated['organization_id'] ?? Organization::first()?->id);

            $otp = rand(100000, 999999);

            $user = User::create([
                'organization_id' => $orgId,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'] ?? User::ROLE_CUSTOMER,
                'otp_code' => $otp,
                'otp_expires_at' => now()->addMinutes(15),
                'is_active' => true,
            ]);

            Auth::login($user);

            try {
                AuditLog::log('auth.register', $user, null, ['role' => $user->role], $user->organization_id, $user->id);
            } catch (\Throwable $logError) {
                \Log::warning('AuditLog register: ' . $logError->getMessage());
            }

            return redirect('/')->with('success', 'Registration successful! Welcome to Zacma.');
        } catch (\Throwable $e) {
            \Log::error('Register error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return back()->withInput()->with('error', 'Registration error: ' . $e->getMessage());
        }
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            AuditLog::log('auth.logout', Auth::user(), null, null, Auth::user()->organization_id, Auth::id());
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'You have been logged out.');
    }

    public function showVerifyOtp()
    {
        return view('auth.verify-otp');
    }

    public function verifyOtp(Request $request)
    {
        $request->validate(['otp' => 'required|string']);
        $user = Auth::user();

        if ($user && $user->otp_code === $request->otp && $user->otp_expires_at?->isFuture()) {
            $user->update([
                'phone_verified_at' => now(),
                'otp_code' => null,
                'otp_expires_at' => null,
            ]);
            return redirect($user->getDashboardUrl())->with('success', 'Phone verified successfully!');
        }

        return back()->with('error', 'Invalid or expired OTP code.');
    }

    public function quickLogin(Request $request, string $role = 'super_admin')
    {
        $emailMap = [
            'super_admin' => 'admin@zacma.com',
            'superadmin' => 'admin@zacma.com',
            'admin' => 'admin@zacma.com',
            'reseller' => 'reseller@zacma.com',
            'agency' => 'reseller@zacma.com',
            'customer' => 'customer@zacma.com',
        ];

        $targetEmail = $emailMap[strtolower($role)] ?? 'admin@zacma.com';
        $user = User::where('email', $targetEmail)->first();

        if (!$user && in_array(strtolower($role), ['super_admin', 'superadmin', 'admin'])) {
            $user = User::where('role', User::ROLE_SUPER_ADMIN)->first() ?? User::first();
            if ($user) {
                $user->update(['role' => User::ROLE_SUPER_ADMIN, 'is_active' => true]);
            }
        }

        if ($user) {
            $user->update(['is_active' => true]);
            Auth::login($user);
            $request->session()->regenerate();
            return redirect($user->getDashboardUrl())->with('success', "Signed in as {$user->name} ({$user->role})");
        }

        return redirect()->route('login')->with('error', 'Demo account not found.');
    }
}
