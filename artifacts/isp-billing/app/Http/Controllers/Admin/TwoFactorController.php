<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TwoFactorController extends Controller
{
    protected TwoFactorService $twoFactorService;

    public function __construct(TwoFactorService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    public function setup()
    {
        $user = auth()->user();

        if ($this->twoFactorService->hasTwoFactor($user)) {
            return redirect()->route('admin.2fa.recovery-codes')
                ->with('info', 'Two-factor authentication is already enabled.');
        }

        $secret = $this->twoFactorService->generateSecret();
        session(['2fa_secret' => $secret]);

        $qrCodeUrl = $this->twoFactorService->getQrCodeUrl($user, $secret);

        return view('admin.2fa.setup', compact('secret', 'qrCodeUrl', 'user'));
    }

    public function enable(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $secret = session('2fa_secret');

        if (!$secret) {
            return back()->with('error', 'Session expired. Please start the setup again.');
        }

        if (!$this->twoFactorService->verifyCode($secret, $request->code)) {
            return back()->with('error', 'Invalid verification code. Please try again.');
        }

        $this->twoFactorService->enableTwoFactor(auth()->user(), $secret);
        session()->forget('2fa_secret');

        return redirect()->route('admin.2fa.recovery-codes')
            ->with('success', 'Two-factor authentication has been enabled successfully.');
    }

    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        if (!Hash::check($request->password, auth()->user()->password)) {
            return back()->with('error', 'Incorrect password. Two-factor authentication was not disabled.');
        }

        $this->twoFactorService->disableTwoFactor(auth()->user());

        return back()->with('success', 'Two-factor authentication has been disabled.');
    }

    public function showRecoveryCodes()
    {
        $user = auth()->user();

        if (!$this->twoFactorService->hasTwoFactor($user)) {
            return redirect()->route('admin.2fa.setup')
                ->with('info', 'Please enable two-factor authentication first.');
        }

        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        return view('admin.2fa.recovery-codes', compact('recoveryCodes'));
    }

    public function regenerateRecoveryCodes()
    {
        $user = auth()->user();

        if (!$this->twoFactorService->hasTwoFactor($user)) {
            return redirect()->route('admin.2fa.setup')
                ->with('info', 'Please enable two-factor authentication first.');
        }

        $codes = $this->twoFactorService->generateRecoveryCodes();
        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode($codes)),
        ]);

        return back()->with('success', 'Recovery codes have been regenerated.');
    }

    public function showVerify()
    {
        $user = auth()->user();

        if (!$this->twoFactorService->hasTwoFactor($user)) {
            return redirect()->route('admin.dashboard');
        }

        if (session('2fa_verified')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.2fa.verify');
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = auth()->user();
        $code = $request->code;

        if (strlen($code) === 6 && is_numeric($code)) {
            $secret = decrypt($user->two_factor_secret);
            if ($this->twoFactorService->verifyCode($secret, $code)) {
                session(['2fa_verified' => true]);
                return redirect()->route('admin.dashboard')->with('success', 'Two-factor authentication verified.');
            }
        }

        if (str_contains($code, '-')) {
            if ($this->twoFactorService->verifyRecoveryCode($user, $code)) {
                session(['2fa_verified' => true]);
                return redirect()->route('admin.dashboard')->with('success', 'Verified with recovery code. Please generate new recovery codes.');
            }
        }

        return back()->with('error', 'Invalid verification code. Please try again.');
    }
}
