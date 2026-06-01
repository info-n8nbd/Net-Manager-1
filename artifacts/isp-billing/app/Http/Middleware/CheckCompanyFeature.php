<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\TenantContext;

class CheckCompanyFeature
{
    protected static array $routeFeatureMap = [
        'admin/clients' => 'client',
        'admin/client' => 'client',
        'admin/applications' => 'client',
        'admin/client-recovery' => 'client',

        'admin/billing' => 'billing',
        'admin/payments' => 'billing',
        'admin/corrections' => 'billing',
        'admin/staff-dues' => 'billing',
        'admin/daily-billing' => 'daily_billing',

        'admin/olt' => 'olt',

        'admin/agent' => 'agent',
        'admin/agents' => 'agent',

        'admin/mikrotik' => 'mikrotik',
        'admin/multi-router' => 'mikrotik',

        'admin/network' => 'network',
        'admin/pops' => 'network',
        'admin/topology' => 'network',

        'admin/hotspot' => 'hotspot',

        'admin/vpn' => 'vpn',
        'admin/port-forward' => 'vpn',

        'admin/reseller' => 'reseller',

        'admin/events' => 'events',

        'admin/support' => 'support',
        'admin/knowledge-base' => 'support',

        'admin/tasks' => 'tasks',

        'admin/sales' => 'sales',
        'admin/purchase' => 'sales',

        'admin/inventory' => 'inventory',
        'admin/store' => 'inventory',

        'admin/reports' => 'reports',
        'admin/btrc' => 'reports',
        'admin/zone-analytics' => 'reports',
        'admin/exports' => 'reports',

        'admin/sms' => 'sms',

        'admin/data-transfer' => 'data_transfer',
        'admin/migration' => 'data_transfer',

        'admin/hrm' => 'hrm',
        'admin/staff' => 'hrm',

        'admin/accounts' => 'accounts',
        'admin/accounting' => 'accounts',

        'admin/settings/zones' => 'configuration',
        'admin/settings/areas' => 'configuration',
        'admin/settings/packages' => 'configuration',
        'admin/config' => 'configuration',

        'admin/settings/company' => 'settings',
        'admin/settings/payment' => 'settings',
        'admin/settings/payment-gateway' => 'settings',
        'admin/settings/system' => 'settings',
        'admin/settings/logo' => 'settings',
        'admin/settings/logs' => 'settings',
        'admin/settings/sms' => 'sms',
        'admin/settings/notification' => 'settings',
        'admin/settings/cron' => 'settings',
        'admin/settings/login-animation' => 'settings',
        'admin/settings/client-portal' => 'settings',

        'admin/branches' => 'branches',

        'admin/ipam' => 'ipam',

        'admin/minute-sales' => 'minute_sales',

        'admin/payment-links' => 'payment_links',

        'admin/voice-calls' => 'voice_calls',

        'admin/card-billing' => 'card_billing',

        'admin/whatsapp' => 'whatsapp',

        'admin/security' => 'security',
        'admin/2fa' => 'security',

        'admin/backups' => 'settings',
        'admin/media-servers' => 'media_servers',
    ];

    protected static array $alwaysAllowedPrefixes = [
        'admin/dashboard',
        'admin/profile',
        'admin/api',
        'admin/overrides',
        'admin/menu-permissions',
        'admin/companies',
        'admin/super-admin',
        'admin/system',
    ];

    public function handle(Request $request, Closure $next, ?string $featureKey = null)
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if ($user->user_type === 'super_admin') {
            return $next($request);
        }

        $company = TenantContext::get();
        if (!$company) {
            $company = $user->company ?? null;
        }

        if (!$company) {
            return $next($request);
        }

        $features = $company->enabled_features ?? [];
        if (empty($features)) {
            return $next($request);
        }

        if ($featureKey) {
            if ($this->isAllowed($featureKey, $features)) {
                return $next($request);
            }
            abort(403, 'This feature is not available for your company.');
        }

        $path = $request->path();

        foreach (static::$alwaysAllowedPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $next($request);
            }
        }

        $matchedFeature = $this->resolveFeature($path);

        if (!$matchedFeature) {
            abort(403, 'This feature is not available for your company.');
        }

        if ($this->isAllowed($matchedFeature, $features)) {
            return $next($request);
        }

        abort(403, 'This feature is not available for your company.');
    }

    protected function resolveFeature(string $path): ?string
    {
        $sortedKeys = array_keys(static::$routeFeatureMap);
        usort($sortedKeys, fn($a, $b) => strlen($b) - strlen($a));

        foreach ($sortedKeys as $routePrefix) {
            if (str_starts_with($path, $routePrefix)) {
                return static::$routeFeatureMap[$routePrefix];
            }
        }

        return null;
    }

    protected static array $legacyAliases = [
        'olt'        => ['onu'],
        'accounts'   => ['accounting'],
        'voice_calls' => ['voice_call'],
        'client'     => ['clients'],
    ];

    protected function isAllowed(string $featureKey, array $features): bool
    {
        if (in_array($featureKey, $features)) {
            return true;
        }

        if (str_contains($featureKey, '.')) {
            $parent = explode('.', $featureKey)[0];
            if (in_array($parent, $features)) {
                return true;
            }
        }

        foreach ($features as $f) {
            if (str_starts_with($f, $featureKey . '.')) {
                return true;
            }
        }

        foreach (static::$legacyAliases[$featureKey] ?? [] as $oldKey) {
            if (in_array($oldKey, $features)) {
                return true;
            }
        }

        return false;
    }
}
