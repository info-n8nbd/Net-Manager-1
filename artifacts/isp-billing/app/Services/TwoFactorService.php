<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TwoFactorService
{
    public function generateSecret(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $secret;
    }

    public function getQrCodeUrl(User $user, string $secret): string
    {
        $companyName = config('app.name', 'SmartBilling');
        $email = urlencode($user->email);
        $issuer = urlencode($companyName);
        return "otpauth://totp/{$issuer}:{$email}?secret={$secret}&issuer={$issuer}&digits=6&period=30";
    }

    public function verifyCode(string $secret, string $code): bool
    {
        if (strlen($code) !== 6 || !is_numeric($code)) {
            return false;
        }

        $timeSlice = floor(time() / 30);

        for ($i = -1; $i <= 1; $i++) {
            $calculatedCode = $this->getCode($secret, $timeSlice + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    private function getCode(string $secret, int $timeSlice): string
    {
        $secretKey = $this->base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $value = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );
        return str_pad($value % 1000000, 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $input): string
    {
        $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $input = strtoupper($input);
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        for ($i = 0; $i < strlen($input); $i++) {
            $val = strpos($map, $input[$i]);
            if ($val === false) continue;
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }
        return $output;
    }

    public function enableTwoFactor(User $user, string $secret): void
    {
        $user->update([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode($this->generateRecoveryCodes())),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    public function disableTwoFactor(User $user): void
    {
        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    public function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = Str::random(5) . '-' . Str::random(5);
        }
        return $codes;
    }

    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $codes = json_decode(decrypt($user->two_factor_recovery_codes), true);
        if (!is_array($codes)) return false;

        $index = array_search($code, $codes);
        if ($index === false) return false;

        unset($codes[$index]);
        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode(array_values($codes))),
        ]);
        return true;
    }

    public function hasTwoFactor(User $user): bool
    {
        return !empty($user->two_factor_secret) && !empty($user->two_factor_confirmed_at);
    }
}
