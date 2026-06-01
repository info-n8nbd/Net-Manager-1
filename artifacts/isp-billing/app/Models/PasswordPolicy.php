<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordPolicy extends Model
{
    protected $fillable = [
        'min_length', 'require_uppercase', 'require_lowercase', 'require_numbers',
        'require_special', 'password_expiry_days', 'max_login_attempts',
        'lockout_duration_minutes', 'enforce_2fa_admin', 'enforce_2fa_all',
    ];

    protected $casts = [
        'require_uppercase' => 'boolean',
        'require_lowercase' => 'boolean',
        'require_numbers' => 'boolean',
        'require_special' => 'boolean',
        'enforce_2fa_admin' => 'boolean',
        'enforce_2fa_all' => 'boolean',
    ];

    public static function getPolicy(): self
    {
        return self::first() ?? new self([
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_special' => false,
            'password_expiry_days' => 0,
            'max_login_attempts' => 5,
            'lockout_duration_minutes' => 30,
            'enforce_2fa_admin' => false,
            'enforce_2fa_all' => false,
        ]);
    }

    public function validatePassword(string $password): array
    {
        $errors = [];
        if (strlen($password) < $this->min_length) {
            $errors[] = "Password must be at least {$this->min_length} characters.";
        }
        if ($this->require_uppercase && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }
        if ($this->require_lowercase && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        }
        if ($this->require_numbers && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }
        if ($this->require_special && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character.';
        }
        return $errors;
    }
}
