<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\IpRestriction;
use App\Models\LoginLog;
use App\Models\PasswordPolicy;
use App\Models\User;
use Illuminate\Http\Request;

class SecurityController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            '2fa_enabled' => User::whereNotNull('two_factor_confirmed_at')->count(),
            'failed_logins_today' => LoginLog::failed()->whereDate('created_at', today())->count(),
            'blocked_ips' => IpRestriction::where('type', 'blacklist')->where('is_active', true)->count(),
            'recent_logins' => LoginLog::with('user')->recent(7)->latest()->limit(10)->get(),
        ];

        return view('admin.security.index', compact('stats'));
    }

    public function passwordPolicy()
    {
        $policy = PasswordPolicy::getPolicy();
        return view('admin.security.password-policy', compact('policy'));
    }

    public function updatePasswordPolicy(Request $request)
    {
        $request->validate([
            'min_length' => 'required|integer|min:6|max:128',
            'require_uppercase' => 'nullable|boolean',
            'require_lowercase' => 'nullable|boolean',
            'require_numbers' => 'nullable|boolean',
            'require_special' => 'nullable|boolean',
            'password_expiry_days' => 'required|integer|min:0|max:365',
            'max_login_attempts' => 'required|integer|min:1|max:20',
            'lockout_duration_minutes' => 'required|integer|min:1|max:1440',
            'enforce_2fa_admin' => 'nullable|boolean',
            'enforce_2fa_all' => 'nullable|boolean',
        ]);

        $policy = PasswordPolicy::first() ?? new PasswordPolicy();
        $policy->fill([
            'min_length' => $request->min_length,
            'require_uppercase' => $request->has('require_uppercase'),
            'require_lowercase' => $request->has('require_lowercase'),
            'require_numbers' => $request->has('require_numbers'),
            'require_special' => $request->has('require_special'),
            'password_expiry_days' => $request->password_expiry_days,
            'max_login_attempts' => $request->max_login_attempts,
            'lockout_duration_minutes' => $request->lockout_duration_minutes,
            'enforce_2fa_admin' => $request->has('enforce_2fa_admin'),
            'enforce_2fa_all' => $request->has('enforce_2fa_all'),
        ]);
        $policy->save();

        return back()->with('success', 'Password policy updated successfully.');
    }

    public function loginLogs(Request $request)
    {
        $query = LoginLog::with('user')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('email')) {
            $query->where('email', 'ILIKE', "%{$request->email}%");
        }

        if ($request->filled('ip_address')) {
            $query->where('ip_address', $request->ip_address);
        }

        if ($request->filled('user_type')) {
            $query->whereHas('user', fn($q) => $q->where('user_type', $request->user_type));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:50);

        return view('admin.security.login-logs', compact('logs'));
    }

    public function ipRestrictions()
    {
        $restrictions = IpRestriction::with(['user', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);

        return view('admin.security.ip-restrictions', compact('restrictions'));
    }

    public function storeIpRestriction(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|string|max:45',
            'type' => 'required|string|in:allow,deny',
            'role' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
        ]);

        IpRestriction::create([
            'ip_address' => $request->ip_address,
            'type' => $request->type,
            'role' => $request->role,
            'description' => $request->description,
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'IP restriction added successfully.');
    }

    public function destroyIpRestriction(IpRestriction $ipRestriction)
    {
        $ipRestriction->delete();
        return back()->with('success', 'IP restriction removed successfully.');
    }

    public function apiTokens()
    {
        $tokens = ApiToken::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);

        return view('admin.security.api-tokens', compact('tokens'));
    }

    public function createApiToken(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'nullable|array',
            'abilities.*' => 'string',
            'expires_at' => 'nullable|date|after:today',
        ]);

        $plainToken = ApiToken::generateToken();

        ApiToken::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'token' => hash('sha256', $plainToken),
            'abilities' => $request->permissions ?? ['*'],
            'expires_at' => $request->expires_at,
        ]);

        return back()->with('token', $plainToken)->with('success', 'API token created successfully. Copy your token below before leaving this page!');
    }

    public function disableApiToken(ApiToken $token)
    {
        if ($token->is_active) {
            $token->update(['is_active' => false]);
            $msg = 'API token disabled successfully.';
        } else {
            $token->update(['is_active' => true]);
            $msg = 'API token enabled successfully.';
        }
        return back()->with('success', $msg);
    }

    public function revokeApiToken(ApiToken $token)
    {
        $token->delete();
        return back()->with('success', 'API token deleted successfully.');
    }

    public function activeSessions()
    {
        $lifetime = config('session.lifetime', 30);
        \App\Models\SessionLog::where('is_active', true)
            ->where('last_activity_at', '<', now()->subMinutes($lifetime))
            ->update(['is_active' => false, 'logout_at' => now(), 'logout_reason' => 'session_expired']);

        $sessions = \App\Models\SessionLog::with('user')
            ->where('is_active', true)
            ->orderBy('last_activity_at', 'desc')
            ->get();

        $totalActive = $sessions->count();
        return view('admin.security.active-sessions', compact('sessions', 'totalActive'));
    }

    public function toggleLocationCapture(\Illuminate\Http\Request $request)
    {
        $current = \App\Models\SystemSetting::get('location_capture_enabled', '1');
        $new = ($current === '0') ? '1' : '0';
        \App\Models\SystemSetting::set('location_capture_enabled', $new, 'security');
        \App\Models\SystemSetting::setGlobal('location_capture_enabled', $new, 'security');
        $msg = ($new === '1') ? 'Location capture enabled.' : 'Location capture disabled.';
        return back()->with('success', $msg);
    }

    public function clearLoginLogs(\Illuminate\Http\Request $request)
    {
        if (auth()->user()->user_type !== 'super_admin') {
            return back()->with('error', 'Unauthorized.');
        }
        LoginLog::withoutGlobalScopes()->truncate();
        return back()->with('success', 'Login history cleared successfully.');
    }


    public function forceLogoutSession(\App\Models\SessionLog $session)
    {
        $session->update([
            'is_active'     => false,
            'logout_at'     => now(),
            'logout_reason' => 'force_logout',
        ]);
        return back()->with('success', 'Session terminated successfully.');
    }

}