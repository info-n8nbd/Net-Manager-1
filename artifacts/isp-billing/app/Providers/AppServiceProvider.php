<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use App\Models\User;
use App\Models\MediaServer;
use App\Models\SystemSetting;
use App\Models\Company;
use App\Services\MenuPermissionService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFour();
        
        $this->autoSeedAdminUser();
        $this->shareMediaServers();
        $this->shareLogoSettings();
        $this->shareActionPermissions();
    }
    
    protected function shareLogoSettings(): void
    {
        View::composer('*', function ($view) {
            try {
                $siteLogo = '/images/logo.png';
                $siteLogoLight = '';
                $companyName = 'SOFTYNEX';
                $companyTagline = 'Connecting You to the Future';
                $loginAnimationWords = '';
                $loginAnimationOpacity = 40;

                if (Schema::hasTable('system_settings')) {
                    $user = auth()->user();
                    $companyId = ($user && $user->company_id) ? $user->company_id : null;

                    if ($companyId) {
                        $getForCompany = function ($key, $default) use ($companyId) {
                            $setting = SystemSetting::withoutGlobalScopes()
                                ->where('key', $key)
                                ->where('company_id', $companyId)
                                ->value('value');
                            if ($setting !== null) return $setting;
                            $fallback = SystemSetting::withoutGlobalScopes()
                                ->where('key', $key)
                                ->where('company_id', 1)
                                ->value('value');
                            return $fallback ?? $default;
                        };

                        $companyRecord = $user->company;
                        if ($companyRecord) {
                            $companyName = $companyRecord->name;
                        }
                        $siteLogo = $getForCompany('site_logo', '/images/logo.png');
                        $siteLogoLight = $getForCompany('site_logo_light', '');
                        $companyTagline = $getForCompany('company_tagline', 'Connecting You to the Future');
                        $loginAnimationWords = $getForCompany('login_animation_words', '');
                        $loginAnimationOpacity = $getForCompany('login_animation_opacity', 40);
                    } else {
                        // Unauthenticated (login page): read from company_id=1 (master company)
                        // then fall back to any global row, then to hardcoded defaults.
                        $getGlobal = function ($key, $default) {
                            $row = SystemSetting::withoutGlobalScopes()
                                ->where('key', $key)
                                ->orderByRaw('CASE WHEN company_id = 1 THEN 0 ELSE 1 END')
                                ->first();
                            if ($row && $row->value !== null && $row->value !== '') {
                                return $row->value;
                            }
                            // Also check Company model name for company_name
                            return $default;
                        };
                        $companyRecord1 = \App\Models\Company::find(1);
                        $siteLogo = $getGlobal('site_logo', '/images/logo.png');
                        $siteLogoLight = $getGlobal('site_logo_light', '');
                        $companyName = $companyRecord1 ? $companyRecord1->name : $getGlobal('company_name', 'SOFTYNEX');
                        $companyTagline = $getGlobal('company_tagline', 'Connecting You to the Future');
                        $loginAnimationWords = $getGlobal('login_animation_words', '');
                        $loginAnimationOpacity = $getGlobal('login_animation_opacity', 40);
                    }
                }

                $view->with('siteLogo', $siteLogo);
                $view->with('siteLogoLight', $siteLogoLight ?: $siteLogo);
                $view->with('companyName', $companyName);
                $view->with('companyTagline', $companyTagline);
                $view->with('loginAnimationWords', $loginAnimationWords);
                $view->with('loginAnimationOpacity', $loginAnimationOpacity);
                // Super admin company switcher
                $__isSA = $user && ($user->user_type === 'super_admin' || (method_exists($user, 'hasRole') && $user->hasRole('Super Admin')));
                $__allCo = collect();
                $__selCo = null;
                if ($__isSA) {
                    try {
                        $__allCo = \App\Models\Company::orderBy('name')->get();
                        $__selId = session('global_company_id');
                        if ($__selId) { $__selCo = $__allCo->firstWhere('id', $__selId); }
                    } catch (\Exception $__ex) {}
                }
                $view->with('currentCompany', null);
                $view->with('isSuperAdmin', $__isSA);
                $view->with('allCompaniesNav', $__allCo);
                $view->with('selectedCompanyNav', $__selCo);
                $view->with('isImpersonating', false);
            } catch (\Exception $e) {
                $view->with('siteLogo', '/images/logo.png');
                $view->with('siteLogoLight', '/images/logo.png');
                $view->with('companyName', 'SOFTYNEX');
                $view->with('companyTagline', 'Connecting You to the Future');
                $view->with('loginAnimationWords', '');
                $view->with('loginAnimationOpacity', 40);
                $view->with('currentCompany', null);
                $view->with('isSuperAdmin', false);
                $view->with('allCompaniesNav', collect());
                $view->with('selectedCompanyNav', null);
                $view->with('isImpersonating', false);
            }
        });
    }
    
    protected function autoSeedAdminUser(): void
    {
        try {
            if (!Schema::hasTable('users')) {
                return;
            }
            
            if (User::count() === 0) {
                User::create([
                    'name' => 'Admin User',
                    'email' => 'admin@isp.com',
                    'password' => Hash::make('password'),
                    'role' => 'admin',
                    'email_verified_at' => now(),
                ]);
                
                \Log::info('Auto-seeded admin user: admin@isp.com');
            }
        } catch (\Exception $e) {
            \Log::warning('Auto-seed skipped: ' . $e->getMessage());
        }
    }

    protected function shareMediaServers(): void
    {
        View::composer(["layouts.admin", "layouts.client", "layouts.reseller", "layouts.billman", "layouts.manager", "layouts.technician", "layouts.sub-reseller", "layouts.card-distributor"], function ($view) {
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable("media_servers") && auth()->check()) {
                    $_cid = auth()->user()->company_id ?? null;
                    $view->with("mediaServers", MediaServer::when($_cid, fn($q) => $q->where("company_id", $_cid))->active()->get());
                } else {
                    $view->with("mediaServers", collect());
                }
            } catch (\Exception $e) {
                $view->with("mediaServers", collect());
            }
        });
    }

    protected function shareActionPermissions(): void
    {
        View::composer('admin.*', function ($view) {
            $user = auth()->user();
            $canAction = function(string $pageKey, string $actionKey) use ($user) {
                try {
                    return MenuPermissionService::canAction($user, $pageKey, $actionKey);
                } catch (\Throwable $e) {
                    \Log::warning('canAction check failed: ' . $e->getMessage());
                    return false;
                }
            };
            $view->with('__canAction', $canAction);

            // Share pending applications count for sidebar badge
            try {
                $pendingCount = 0;
                if (auth()->check()) {
                    $pendingCount = \App\Models\NewConnectionApplication::where('status', 'pending')->count();
                }
                $view->with('pendingApplicationsCount', $pendingCount);
            } catch (\Throwable $e) {
                $view->with('pendingApplicationsCount', 0);
            }
        });
    }
}
