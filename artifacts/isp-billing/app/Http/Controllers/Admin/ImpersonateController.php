<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ImpersonateController extends Controller
{
    public function loginAs(Request $request, User $user)
    {
        $admin = Auth::user();

        $canImpersonate = $admin->hasRole('Super Admin')
            || $admin->hasRole('Administrator')
            || in_array($admin->user_type, ['super_admin', 'admin']);

        if (!$canImpersonate) {
            abort(403, 'Only Super Admin and Admin can impersonate users.');
        }

        if ($user->hasRole('Super Admin')) {
            return back()->with('error', 'Cannot impersonate a Super Admin.');
        }

        if ($admin->id === $user->id) {
            return back()->with('error', 'Cannot impersonate yourself.');
        }

        Session::put('impersonate_original_user_id', $admin->id);
        Session::put('impersonate_original_user_name', $admin->name);

        Auth::login($user);

        $redirect = $this->getDashboardRoute($user);

        return redirect($redirect)->with('success', "You are now logged in as {$user->name}");
    }

    public function switchBack()
    {
        $originalUserId = Session::get('impersonate_original_user_id');

        if (!$originalUserId) {
            return redirect()->route('login')->with('error', 'No impersonation session found.');
        }

        $originalUser = User::find($originalUserId);

        Session::forget('impersonate_original_user_id');
        Session::forget('impersonate_original_user_name');

        if (!$originalUser) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Original user not found.');
        }

        Auth::login($originalUser);

        return redirect()->route('admin.dashboard')->with('success', 'Switched back to your admin account.');
    }

    private function getDashboardRoute(User $user): string
    {
        if ($user->hasRole('Billing Officer')) {
            return route('billman.dashboard');
        }

        $type = $user->user_type;

        return match($type) {
            'reseller' => route('reseller.dashboard'),
            'sub_reseller' => route('sub-reseller.dashboard'),
            'technician' => route('technician.dashboard'),
            'client' => route('client.dashboard'),
            'manager' => route('manager.dashboard'),
            'card_distributor' => route('card-distributor.dashboard'),
            default => route('admin.dashboard'),
        };
    }
}
