<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Package;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OtpSignupController extends Controller
{
    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function showSignupForm()
    {
        $packages = Package::where('is_active', true)->get();
        return view('public.otp-signup', compact('packages'));
    }

    public function sendOtp(Request $request)
    {
        $request->validate(['phone' => 'required|string|min:10|max:15']);

        $result = $this->otpService->generateOtp($request->phone, 'registration', $request->ip());

        if (!$result['success']) {
            return response()->json($result, 429);
        }

        $this->otpService->sendOtpViaSms($request->phone, $result['otp_code']);

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your phone',
            'expires_in' => $result['expires_in'],
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp_code' => 'required|string|size:6',
        ]);

        $result = $this->otpService->verifyOtp($request->phone, $request->otp_code, 'registration');

        return response()->json($result);
    }

    public function register(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|min:10|max:15',
            'name' => 'required|string|max:255',
            'package_id' => 'required|exists:packages,id',
        ]);

        $verification = $this->otpService->verifyOtp($request->phone, $request->otp_code ?? '', 'registration');

        $existingClient = Client::where('phone', $request->phone)->first();
        if ($existingClient) {
            return back()->with('error', 'A client with this phone number already exists');
        }

        $package = Package::findOrFail($request->package_id);
        $username = 'USR-' . Str::random(8);

        $client = Client::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'username' => $username,
            'password' => Str::random(10),
            'package_id' => $request->package_id,
            'monthly_fee' => $package->price,
            'connection_type' => $request->connection_type ?? 'hotspot',
            'status' => 'pending',
            'address' => $request->address ?? null,
        ]);

        return view('public.signup-success', compact('client', 'username'));
    }
}
