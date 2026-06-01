<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ClientForgotPasswordController extends Controller
{
    public function showForgotForm(Request $request)
    {
        return view('auth.client-forgot-password');
    }

    public function verifyIdentity(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'phone' => 'required|string|min:6',
        ]);

        $key = 'client-forgot:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withInput($request->only('identifier', 'phone'))
                ->withErrors(['identifier' => "Too many attempts. Please try again in {$seconds} seconds."]);
        }
        RateLimiter::hit($key, 300);

        $identifier = trim($request->identifier);
        $phone = trim($request->phone);

        $client = Client::where(function ($q) use ($identifier) {
            $q->where('client_id', $identifier)
              ->orWhere('email', $identifier)
              ->orWhere('username', $identifier);
        })->first();

        if (!$client) {
            return back()->withInput($request->only('identifier', 'phone'))
                ->withErrors(['identifier' => 'No client found with this Account Number / Email / Username.']);
        }

        $clientPhone = preg_replace('/[^0-9]/', '', $client->phone ?? '');
        $inputPhone = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($clientPhone) > 4 && strlen($inputPhone) > 4) {
            $matched = str_ends_with($clientPhone, substr($inputPhone, -min(strlen($inputPhone), 11)))
                       || $clientPhone === $inputPhone;
        } else {
            $matched = $clientPhone === $inputPhone;
        }

        if (!$matched) {
            return back()->withInput($request->only('identifier', 'phone'))
                ->withErrors(['phone' => 'Phone number does not match our records for this account.']);
        }

        $token = Str::random(60);

        DB::table('client_password_reset_tokens')->where('phone', $client->phone)->delete();
        DB::table('client_password_reset_tokens')->insert([
            'phone' => $client->phone,
            'token' => Hash::make($token),
            'created_at' => Carbon::now(),
        ]);

        $request->session()->put('password_reset_token', $token);
        $request->session()->put('password_reset_phone', $client->phone);
        $request->session()->put('password_reset_company_id', $client->company_id);
        $request->session()->put('password_reset_expires', Carbon::now()->addMinutes(30)->timestamp);

        $data = [];
        $data['client_name'] = $client->name;
        $data['client_username'] = $client->username;

        return view('auth.client-reset-password', $data);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $token = $request->session()->get('password_reset_token');
        $phone = $request->session()->get('password_reset_phone');
        $companyId = $request->session()->get('password_reset_company_id');
        $expires = $request->session()->get('password_reset_expires');

        if (!$token || !$phone || !$companyId) {
            return redirect()->route('client.forgot')
                ->withErrors(['identifier' => 'Invalid or expired session. Please try again.']);
        }

        if ($expires && Carbon::now()->timestamp > $expires) {
            $this->clearResetSession($request);
            DB::table('client_password_reset_tokens')->where('phone', $phone)->delete();
            return redirect()->route('client.forgot')
                ->withErrors(['identifier' => 'Reset session has expired. Please try again.']);
        }

        $record = DB::table('client_password_reset_tokens')
            ->where('phone', $phone)
            ->first();

        if (!$record || !Hash::check($token, $record->token)) {
            $this->clearResetSession($request);
            return redirect()->route('client.forgot')
                ->withErrors(['identifier' => 'Invalid or expired reset token. Please try again.']);
        }

        if (Carbon::parse($record->created_at)->addMinutes(30)->isPast()) {
            $this->clearResetSession($request);
            DB::table('client_password_reset_tokens')->where('phone', $phone)->delete();
            return redirect()->route('client.forgot')
                ->withErrors(['identifier' => 'Reset token has expired. Please try again.']);
        }

        $client = Client::where('phone', $phone)
            ->where('company_id', $companyId)
            ->first();

        if (!$client) {
            $this->clearResetSession($request);
            return redirect()->route('client.forgot')
                ->withErrors(['identifier' => 'Client not found.']);
        }

        $client->update([
            'pppoe_password' => $request->password,
        ]);

        if ($client->user_id && $client->user) {
            $client->user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        DB::table('client_password_reset_tokens')->where('phone', $phone)->delete();
        $this->clearResetSession($request);

        return redirect()->route('client.login')
            ->with('status', 'Password reset successfully! You can now login with your new password.');
    }

    protected function clearResetSession(Request $request)
    {
        $request->session()->forget([
            'password_reset_token',
            'password_reset_phone',
            'password_reset_company_id',
            'password_reset_expires',
        ]);
    }
}
