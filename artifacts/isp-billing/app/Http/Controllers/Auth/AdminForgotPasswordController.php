<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AdminForgotPasswordController extends Controller
{
    public function showForgotForm()
    {
        return view('auth.admin-forgot-password');
    }

    public function verifyIdentity(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'phone' => 'required|string|min:6',
        ]);

        $key = 'admin-forgot:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => "Too many attempts. Please try again in {$seconds} seconds."]);
        }
        RateLimiter::hit($key, 300);

        $email = trim($request->email);
        $phone = trim($request->phone);

        $user = User::where('email', $email)
            ->where('user_type', '!=', 'client')
            ->first();

        if (!$user) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'No account found with this email address.']);
        }

        if (!$user->is_active) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'This account is currently deactivated. Please contact your administrator.']);
        }

        $userPhone = preg_replace('/[^0-9]/', '', $user->phone ?? '');
        $inputPhone = preg_replace('/[^0-9]/', '', $phone);

        if (empty($userPhone)) {
            return back()->withInput($request->only('email'))
                ->withErrors(['phone' => 'No phone number registered with this account. Please contact your administrator.']);
        }

        if (strlen($userPhone) > 4 && strlen($inputPhone) > 4) {
            $matched = str_ends_with($userPhone, substr($inputPhone, -min(strlen($inputPhone), 11)))
                       || $userPhone === $inputPhone;
        } else {
            $matched = $userPhone === $inputPhone;
        }

        if (!$matched) {
            return back()->withInput($request->only('email'))
                ->withErrors(['phone' => 'Phone number does not match our records for this account.']);
        }

        $token = Str::random(60);

        DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => Carbon::now(),
        ]);

        $request->session()->put('admin_reset_token', $token);
        $request->session()->put('admin_reset_email', $user->email);
        $request->session()->put('admin_reset_expires', Carbon::now()->addMinutes(30)->timestamp);

        return view('auth.admin-reset-password', [
            'user_name' => $user->name,
            'user_email' => $user->email,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $token = $request->session()->get('admin_reset_token');
        $email = $request->session()->get('admin_reset_email');
        $expires = $request->session()->get('admin_reset_expires');

        if (!$token || !$email) {
            return redirect()->route('admin.forgot')
                ->withErrors(['email' => 'Invalid or expired session. Please try again.']);
        }

        if ($expires && Carbon::now()->timestamp > $expires) {
            $this->clearResetSession($request);
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return redirect()->route('admin.forgot')
                ->withErrors(['email' => 'Reset session has expired. Please try again.']);
        }

        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$record || !Hash::check($token, $record->token)) {
            $this->clearResetSession($request);
            return redirect()->route('admin.forgot')
                ->withErrors(['email' => 'Invalid or expired reset token. Please try again.']);
        }

        if (Carbon::parse($record->created_at)->addMinutes(30)->isPast()) {
            $this->clearResetSession($request);
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            return redirect()->route('admin.forgot')
                ->withErrors(['email' => 'Reset token has expired. Please try again.']);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->clearResetSession($request);
            return redirect()->route('admin.forgot')
                ->withErrors(['email' => 'User not found.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'password_changed_at' => Carbon::now(),
        ]);

        DB::table('password_reset_tokens')->where('email', $email)->delete();
        $this->clearResetSession($request);

        return redirect()->route('login')
            ->with('status', 'Password reset successfully! You can now login with your new password.');
    }

    protected function clearResetSession(Request $request)
    {
        $request->session()->forget([
            'admin_reset_token',
            'admin_reset_email',
            'admin_reset_expires',
        ]);
    }
}
