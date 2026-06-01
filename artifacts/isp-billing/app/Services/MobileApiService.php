<?php

namespace App\Services;

use App\Models\User;
use App\Models\Client;
use App\Models\ApiRefreshToken;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class MobileApiService
{
    const ACCESS_TOKEN_EXPIRY_MINUTES = 60;
    const REFRESH_TOKEN_EXPIRY_DAYS = 30;

    public function authenticate(string $email, string $password, ?string $deviceName = null, ?string $deviceId = null): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        if (!$user->is_active) {
            return ['success' => false, 'message' => 'Account is inactive'];
        }

        $rawAccessToken = Str::random(64);
        $rawRefreshToken = Str::random(64);

        ApiRefreshToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $rawAccessToken),
            'device_name' => $deviceName ?? 'mobile',
            'device_id' => $deviceId,
            'ip_address' => request()->ip(),
            'expires_at' => now()->addMinutes(self::ACCESS_TOKEN_EXPIRY_MINUTES),
        ]);

        ApiRefreshToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $rawRefreshToken),
            'device_name' => ($deviceName ?? 'mobile') . '_refresh',
            'device_id' => $deviceId,
            'ip_address' => request()->ip(),
            'expires_at' => now()->addDays(self::REFRESH_TOKEN_EXPIRY_DAYS),
        ]);

        return [
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'user_type' => $user->user_type,
            ],
            'access_token' => $rawAccessToken,
            'refresh_token' => $rawRefreshToken,
            'token_type' => 'Bearer',
            'expires_in' => self::ACCESS_TOKEN_EXPIRY_MINUTES * 60,
        ];
    }

    public function authenticateByPppoe(string $username, string $pppoePassword, ?string $deviceName = null, ?string $deviceId = null): array
    {
        $client = Client::where('username', $username)->first();

        if (!$client) {
            return ['success' => false, 'message' => 'No client found with this PPPoE username.'];
        }

        if ($client->pppoe_password !== $pppoePassword) {
            return ['success' => false, 'message' => 'Incorrect PPPoE password.'];
        }

        if ($client->status === 'deleted') {
            return ['success' => false, 'message' => 'Account is inactive or deleted.'];
        }

        // Find or create associated user account
        $email = $client->email ?? ($client->username . '@client.local');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'name'       => $client->name,
                'email'      => $email,
                'password'   => Hash::make($pppoePassword),
                'phone'      => $client->phone,
                'user_type'  => 'client',
                'is_active'  => true,
            ]);
        }

        if (!$user->is_active) {
            return ['success' => false, 'message' => 'Account is inactive.'];
        }

        $rawAccessToken  = Str::random(64);
        $rawRefreshToken = Str::random(64);

        ApiRefreshToken::create([
            'user_id'     => $user->id,
            'token'       => hash('sha256', $rawAccessToken),
            'device_name' => $deviceName ?? 'mobile',
            'device_id'   => $deviceId,
            'ip_address'  => request()->ip(),
            'expires_at'  => now()->addMinutes(self::ACCESS_TOKEN_EXPIRY_MINUTES),
        ]);

        ApiRefreshToken::create([
            'user_id'     => $user->id,
            'token'       => hash('sha256', $rawRefreshToken),
            'device_name' => ($deviceName ?? 'mobile') . '_refresh',
            'device_id'   => $deviceId,
            'ip_address'  => request()->ip(),
            'expires_at'  => now()->addDays(self::REFRESH_TOKEN_EXPIRY_DAYS),
        ]);

        return [
            'success' => true,
            'user' => [
                'id'        => $user->id,
                'name'      => $client->name,
                'email'     => $email,
                'phone'     => $client->phone,
                'user_type' => 'client',
                'username'  => $client->username,
            ],
            'access_token'  => $rawAccessToken,
            'refresh_token' => $rawRefreshToken,
            'token_type'    => 'Bearer',
            'expires_in'    => self::ACCESS_TOKEN_EXPIRY_MINUTES * 60,
        ];
    }

    public function refreshAccessToken(string $refreshToken): array
    {
        $hashedToken = hash('sha256', $refreshToken);
        $storedToken = ApiRefreshToken::where('token', $hashedToken)
            ->where('is_revoked', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$storedToken) {
            return ['success' => false, 'message' => 'Invalid or expired refresh token'];
        }

        $user = User::find($storedToken->user_id);
        if (!$user || !$user->is_active) {
            return ['success' => false, 'message' => 'User not found or inactive'];
        }

        $storedToken->update(['last_used_at' => now()]);

        $rawAccessToken = Str::random(64);
        ApiRefreshToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $rawAccessToken),
            'device_name' => $storedToken->device_name,
            'device_id' => $storedToken->device_id,
            'ip_address' => request()->ip(),
            'expires_at' => now()->addMinutes(self::ACCESS_TOKEN_EXPIRY_MINUTES),
        ]);

        return [
            'success' => true,
            'access_token' => $rawAccessToken,
            'token_type' => 'Bearer',
            'expires_in' => self::ACCESS_TOKEN_EXPIRY_MINUTES * 60,
        ];
    }

    public function revokeRefreshToken(string $refreshToken): bool
    {
        return ApiRefreshToken::where('token', hash('sha256', $refreshToken))
            ->update(['is_revoked' => true]) > 0;
    }

    public function revokeAllTokens(int $userId): int
    {
        return ApiRefreshToken::where('user_id', $userId)
            ->where('is_revoked', false)
            ->update(['is_revoked' => true]);
    }
}
