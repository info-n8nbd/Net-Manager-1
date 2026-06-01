<?php

namespace App\Services;

use App\Models\Company;

class TenantContext
{
    private static ?Company $currentCompany = null;

    public static function set(?Company $company): void
    {
        static::$currentCompany = $company;
    }

    public static function get(): ?Company
    {
        return static::$currentCompany;
    }

    public static function id(): ?int
    {
        return static::$currentCompany?->id ?? 1;
    }

    public static function setSuperAdminMode(bool $mode = true): void
    {
    }

    public static function isSuperAdmin(): bool
    {
        return false;
    }

    public static function hasFeature(string $feature): bool
    {
        return true;
    }

    public static function reset(): void
    {
        static::$currentCompany = null;
    }
}
