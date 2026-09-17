<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Setting;
use App\Models\SmmOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        return redirect()->route('super-admin.smm.dashboard');
    }

    public function users(Request $request)
    {
        $query = User::with(['organization', 'wallet']);
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->q}%")
                  ->orWhere('email', 'like', "%{$request->q}%")
                  ->orWhere('phone', 'like', "%{$request->q}%");
            });
        }
        $users = $query->latest()->paginate(20);
        return view('super-admin.users.index', compact('users'));
    }

    public function toggleUserStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Cannot deactivate your own super admin account.');
        }
        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'activated' : 'deactivated';
        AuditLog::log('super_admin.toggle_user_status', $user);
        return back()->with('success', "User account {$user->name} {$status}.");
    }

    public function impersonate(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You are already logged in as this user.');
        }

        $superAdminId = auth()->id();
        AuditLog::log('super_admin.impersonate_user', $user, null, ['impersonated_user_id' => $user->id]);

        auth()->login($user);
        session(['impersonator_id' => $superAdminId]);

        return redirect($user->getDashboardUrl())->with('info', "Now viewing platform as {$user->name} ({$user->role}).");
    }

    public function stopImpersonation()
    {
        $superAdminId = session('impersonator_id');
        if (!$superAdminId) {
            return redirect()->route('home');
        }

        $superAdmin = User::find($superAdminId);
        if ($superAdmin && $superAdmin->isSuperAdmin()) {
            auth()->login($superAdmin);
            session()->forget('impersonator_id');
            AuditLog::log('super_admin.stop_impersonation', $superAdmin);
            return redirect()->route('super-admin.smm.dashboard')->with('success', 'Restored Super Admin Command session.');
        }

        session()->forget('impersonator_id');
        return redirect()->route('home');
    }

    public function auditLogs()
    {
        $logs = AuditLog::with(['organization', 'user'])->latest()->paginate(30);
        return view('super-admin.audit-logs.index', compact('logs'));
    }

    public function settings()
    {
        $settings = Setting::whereNull('organization_id')->get()->pluck('value', 'key');
        return view('super-admin.settings.index', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $allowed = [
            'platform_name', 'support_email', 'gemini_api_key', 'gemini_model',
            'santimpay_merchant_id', 'santimpay_private_key', 'telebirr_app_id',
            'telebirr_app_key', 'telebirr_short_code', 'chapa_secret_key',
            'paypal_client_id', 'paypal_secret', 'stripe_secret_key'
        ];

        foreach ($request->only($allowed) as $key => $val) {
            Setting::set($key, $val, 'integrations', null);
        }

        AuditLog::log('super_admin.update_settings');

        return back()->with('success', 'Platform settings and integration keys updated successfully!');
    }
}
