<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;
use App\Services\TenantContext;

class SystemSetting extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = ["key", "value", "group", "company_id"];

    /**
     * Resolve the current company_id for the authenticated user.
     */
    protected static function resolveCompanyId(): ?int
    {
        if (auth()->check() && auth()->user()->company_id) {
            return auth()->user()->company_id;
        }
        if (TenantContext::get()) {
            return TenantContext::get()->id;
        }
        return 1;
    }

    /**
     * Get a setting value for the current tenant.
     * Always scoped by the authenticated user company_id explicitly
     * to prevent SuperAdmin cross-tenant data leaks.
     */
    public static function get($key, $default = null)
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable("system_settings")) {
                return $default;
            }

            $companyId = static::resolveCompanyId();
            $setting = static::withoutGlobalScopes()
                ->where("key", $key)
                ->where("company_id", $companyId)
                ->first();

            if ($setting) {
                $val = $setting->value;
                return ($val !== null && $val !== "") ? $val : $default;
            }

            return $default;
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Set a setting value for the current tenant.
     * Always scoped by company_id explicitly to prevent SuperAdmin cross-tenant updates.
     */
    public static function set($key, $value, $group = "general")
    {
        $companyId = static::resolveCompanyId();

        $existing = static::withoutGlobalScopes()
            ->where("key", $key)
            ->where("company_id", $companyId)
            ->first();

        if ($existing) {
            $existing->value = $value;
            $existing->group = $group;
            $existing->save();
            return $existing;
        }

        return static::withoutGlobalScopes()->create([
            "key"        => $key,
            "value"      => $value,
            "group"      => $group,
            "company_id" => $companyId,
        ]);
    }

    /**
     * Get a GLOBAL setting (company_id = NULL) — stored by Super Admin, visible to all.
     */
    public static function getGlobal($key, $default = null)
    {
        try {
            $setting = static::withoutGlobalScopes()->whereNull("company_id")->where("key", $key)->first();
            return ($setting && $setting->value !== null && $setting->value !== "") ? $setting->value : $default;
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Set a GLOBAL setting (company_id = NULL) — only Super Admin should call this.
     */
    public static function setGlobal($key, $value, $group = "global")
    {
        $existing = static::withoutGlobalScopes()->whereNull("company_id")->where("key", $key)->first();
        if ($existing) {
            $existing->value = $value;
            $existing->group = $group;
            $existing->save();
            return $existing;
        }
        return static::withoutGlobalScopes()->create(["key" => $key, "value" => $value, "group" => $group, "company_id" => null]);
    }

    public static function all($columns = ["*"])
    {
        return parent::all($columns);
    }
}
