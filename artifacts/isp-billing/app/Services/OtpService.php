<?php

namespace App\Services;

use App\Models\OtpVerification;
use Illuminate\Support\Facades\Cache;

class OtpService
{
    const MAX_ATTEMPTS = 5;
    const OTP_EXPIRY_MINUTES = 5;
    const MAX_REQUESTS_PER_HOUR = 10;

    public function generateOtp(string $phone, string $purpose = 'registration', ?string $ipAddress = null): array
    {
        if ($this->isRateLimited($phone)) {
            return ['success' => false, 'message' => 'Too many OTP requests. Please try again later.'];
        }

        OtpVerification::where('phone', $phone)
            ->where('purpose', $purpose)
            ->where('is_verified', false)
            ->delete();

        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otp = OtpVerification::create([
            'phone' => $phone,
            'otp_code' => bcrypt($otpCode),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
            'ip_address' => $ipAddress,
        ]);

        $this->incrementRateLimit($phone);

        return [
            'success' => true,
            'otp_code' => $otpCode,
            'expires_in' => self::OTP_EXPIRY_MINUTES * 60,
            'otp_id' => $otp->id,
        ];
    }

    public function verifyOtp(string $phone, string $otpCode, string $purpose = 'registration'): array
    {
        $otp = OtpVerification::where('phone', $phone)
            ->where('purpose', $purpose)
            ->where('is_verified', false)
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$otp) {
            return ['success' => false, 'message' => 'OTP not found or expired'];
        }

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            return ['success' => false, 'message' => 'Maximum verification attempts exceeded'];
        }

        $otp->increment('attempts');

        if (!password_verify($otpCode, $otp->otp_code)) {
            return ['success' => false, 'message' => 'Invalid OTP code', 'remaining_attempts' => self::MAX_ATTEMPTS - $otp->attempts];
        }

        $otp->update(['is_verified' => true, 'verified_at' => now()]);

        return ['success' => true, 'message' => 'OTP verified successfully'];
    }

    protected function isRateLimited(string $phone): bool
    {
        return Cache::get('otp_rate_' . $phone, 0) >= self::MAX_REQUESTS_PER_HOUR;
    }

    protected function incrementRateLimit(string $phone): void
    {
        $key = 'otp_rate_' . $phone;
        Cache::put($key, Cache::get($key, 0) + 1, now()->addHour());
    }

    public function sendOtpViaSms(string $phone, string $otpCode): bool
    {
        try {
            $smsService = app(SmsService::class);
            $message = "Your verification code is: {$otpCode}. Valid for " . self::OTP_EXPIRY_MINUTES . " minutes.";
            $smsService->sendSms($phone, $message);
            return true;
        } catch (\Exception $e) {
            \Log::error('OTP SMS sending failed: ' . $e->getMessage());
            return false;
        }
    }
}
