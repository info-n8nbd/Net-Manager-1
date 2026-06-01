<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function showLoginForm(Request $request)
    {
        if (Auth::check()) {
            return $this->redirectBasedOnUserType();
        }
        return view('auth.login');
    }
    
    public function showClientLoginForm(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user && ($user->user_type === 'client' || $user->hasRole('client'))) {
                return redirect()->route('client.dashboard');
            }
        }
        return view('auth.client-login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();
            if (!$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                return back()->withInput($request->only('email'))
                    ->withErrors(['email' => 'Your account has been disabled. Please contact your administrator.']);
            }

            // Check if company is active (skip for super_admin)
            if ($user->user_type !== 'super_admin' && $user->company_id) {
                $company = \App\Models\Company::find($user->company_id);
                if ($company && !$company->is_active) {
                    Auth::logout();
                    $request->session()->invalidate();
                    return back()->withInput($request->only('email'))
                        ->withErrors(['email' => 'This company account has been deactivated. Please contact support.']);
                }
            }
            $request->session()->regenerate();
            // Log active session
            try {
                \App\Models\SessionLog::logLogin($user, $request->session()->getId());
            } catch (\Exception $e) {}
            // Log login activity
            try {
                $locationEnabled = \App\Models\SystemSetting::getGlobal('location_capture_enabled', '1');
                $lat = ($locationEnabled !== '0') ? $request->input('_lat') : null;
                $lng = ($locationEnabled !== '0') ? $request->input('_lng') : null;
                $locationAddress = ($locationEnabled !== '0') ? $request->input('_location_address') : null;
                \App\Models\LoginLog::create([
                    'user_id'          => $user->id,
                    'email'            => $user->email,
                    'ip_address'       => $request->ip(),
                    'user_agent'       => $request->userAgent(),
                    'status'           => 'success',
                    'latitude'         => $lat ?: null,
                    'longitude'        => $lng ?: null,
                    'location_address' => $locationAddress ?: null,
                    'session_id'       => $request->session()->getId(),
                ]);
            } catch (\Exception $e) {}
            return $this->redirectBasedOnUserType();
        }

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => 'The provided credentials do not match our records.']);
    }
    
    public function clientLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'pppoe_password' => 'required|string',
        ]);
        
        $client = Client::where('username', $request->username)->first();
        
        if (!$client) {
            return back()->withInput($request->only('username'))
                ->withErrors(['username' => 'No client found with this PPPoE username.']);
        }
        
        if ($client->pppoe_password !== $request->pppoe_password) {
            return back()->withInput($request->only('username'))
                ->withErrors(['pppoe_password' => 'Incorrect PPPoE password.']);
        }
        
        if (!$client->user_id) {
            $email = $client->email ?? $client->username . '@client.local';
            
            $user = User::where('email', $email)->first();
            
            if (!$user) {
                $user = User::create([
                    'name' => $client->name,
                    'email' => $email,
                    'password' => Hash::make($request->pppoe_password),
                    'user_type' => 'client',
                    'company_id' => $client->company_id,
                ]);
                $user->assignRole('client');
            }
            
            $client->update(['user_id' => $user->id]);
            $client->refresh();
        }
        
        if (!$client->user) {
            return back()->withInput($request->only('username'))
                ->withErrors(['username' => 'Unable to create user account. Please contact support.']);
        }
        
        if ($client->user->user_type !== 'client') {
            $client->user->update(['user_type' => 'client']);
            if (!$client->user->hasRole('client')) {
                $client->user->assignRole('client');
            }
        }
        
        Auth::login($client->user, $request->boolean('remember'));
        $request->session()->regenerate();
        // Log login activity with location
        try {
            $lat             = $request->input('_lat');
            $lng             = $request->input('_lng');
            $locationAddress = $request->input('_location_address');
            \App\Models\LoginLog::create([
                'user_id'          => $client->user->id,
                'email'            => $client->user->email,
                'ip_address'       => $request->ip(),
                'user_agent'       => $request->userAgent(),
                'status'           => 'success',
                'latitude'         => $lat ?: null,
                'longitude'        => $lng ?: null,
                'location_address' => $locationAddress ?: null,
                'session_id'       => $request->session()->getId(),
            ]);
        } catch (\Exception $e) {}
        return redirect()->route('client.dashboard');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        $isClient = $user && ($user->user_type === 'client' || $user->hasRole('client'));
        
        // Log session logout
        try { \App\Models\SessionLog::logLogout($request->session()->getId(), "user_logout"); } catch (\Exception $e) {}
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        if ($isClient) {
            return redirect()->route('client.login');
        }
        
        return redirect()->route('login');
    }

    protected function redirectBasedOnUserType()
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        if ($user->user_type === 'super_admin' || $user->hasRole('Super Admin')) {
            return redirect()->route('admin.dashboard');
        }
        // Admin-panel roles → always go to admin dashboard
        $adminPanelRoles = ['Administrator', 'Admin Support', 'Assistant Manager', 'Branch Manager',
                            'Supervisor', 'Collection Officer', 'Network Engineer', 'Support Executive'];
        foreach ($adminPanelRoles as $role) {
            if ($user->hasRole($role)) {
                return redirect()->route('admin.dashboard');
            }
        }
        if ($user->hasRole('Field Technician') || $user->user_type === 'technician') {
            return redirect()->route('technician.dashboard');
        }
        if ($user->hasRole('Billing Officer') || $user->user_type === 'billman') {
            return redirect()->route('billman.dashboard');
        }
        if ($user->user_type === 'manager') {
            return redirect()->route('manager.dashboard');
        }
        if ($user->hasRole('Reseller') || $user->user_type === 'reseller') {
            return redirect()->route('reseller.dashboard');
        }
        if ($user->hasRole('Sub Reseller') || $user->user_type === 'sub_reseller') {
            return redirect()->route('sub-reseller.dashboard');
        }
        if ($user->hasRole('Card Agent') || $user->user_type === 'card_distributor') {
            return redirect()->route('card-distributor.dashboard');
        }
        if ($user->user_type === 'client' || $user->hasRole('client')) {
            return redirect()->route('client.dashboard');
        }
        if (in_array($user->user_type, ['admin', 'employee']) || $user->hasRole('Administrator')) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('admin.dashboard');
    }
    /**
     * Real-time location update for already-logged-in sessions.
     * Called via AJAX from layout JS after geolocation resolves.
     */
    public function updateMyLocation(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'lat'     => 'required|numeric|between:-90,90',
            'lng'     => 'required|numeric|between:-180,180',
            'address' => 'nullable|string|max:500',
        ]);

        $sessionId = $request->session()->getId();
        $userId    = auth()->id();

        // Try to find by session_id first (most accurate)
        $log = \App\Models\LoginLog::where('session_id', $sessionId)
                    ->where('status', 'success')
                    ->latest()
                    ->first();

        // Fallback: most recent success log for this user today
        if (!$log) {
            $log = \App\Models\LoginLog::where('user_id', $userId)
                        ->where('status', 'success')
                        ->whereDate('created_at', today())
                        ->latest()
                        ->first();
        }

        if (!$log) {
            return response()->json(['status' => 'no_log'], 404);
        }

        $log->update([
            'latitude'         => $request->lat,
            'longitude'        => $request->lng,
            'location_address' => $request->address,
        ]);

        return response()->json(['status' => 'updated']);
    }

}
