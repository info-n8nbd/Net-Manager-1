<?php

// Load environment variables from various sources (for Replit deployment compatibility)
require_once __DIR__.'/env_loader.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*', headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO);
        
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->append(\App\Http\Middleware\AntiScrapingMiddleware::class);
        
        $middleware->priority([
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
        
        $middleware->validateCsrfTokens(except: [
            '/api/sms/receive',
            'api/v1/*',
            '/payment/ssl/success',
            '/payment/ssl/fail',
            '/payment/ssl/cancel',
            '/payment/ssl/success',
            '/payment/ssl/fail',
            '/payment/ssl/cancel',
            '/payment/bkash/callback',
            '/payment/nagad/callback',
            '/payment/ipay/success',
            '/payment/ipay/cancel',
            '/webhook/payment/*',
            '/webhook/voice/*',
            '/api/self-service/lookup',
            '/api/speed-test/upload',
            '/api/speed-test/save',
        ]);
        
        $middleware->alias([
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'client.access' => \App\Http\Middleware\ClientMiddleware::class,
            'technician' => \App\Http\Middleware\TechnicianMiddleware::class,
            'billman' => \App\Http\Middleware\BillmanMiddleware::class,
            'reseller' => \App\Http\Middleware\ResellerMiddleware::class,
            'sub_reseller' => \App\Http\Middleware\SubResellerMiddleware::class,
            'manager' => \App\Http\Middleware\ManagerMiddleware::class,
            'card_distributor' => \App\Http\Middleware\CardDistributorMiddleware::class,
            'api.token' => \App\Http\Middleware\ApiTokenMiddleware::class,
            'check.permission' => \App\Http\Middleware\CheckPermission::class,
            'prevent.duplicate' => \App\Http\Middleware\PreventDuplicateSubmission::class,
            'ip.restriction' => \App\Http\Middleware\IpRestriction::class,
            'system.permission' => \App\Http\Middleware\EnforceSystemPermission::class,
            'protect.superadmin' => \App\Http\Middleware\ProtectSuperAdmin::class,
            '2fa' => \App\Http\Middleware\TwoFactorMiddleware::class,
            'menu.access' => \App\Http\Middleware\CheckMenuAccess::class,
            'super_admin' => \App\Http\Middleware\SuperAdminMiddleware::class,
            'route.permission' => \App\Http\Middleware\CheckRoutePermission::class,
            'mac.reseller' => \App\Http\Middleware\MacResellerOnly::class,
            'reseller.feature' => \App\Http\Middleware\ResellerFeatureMiddleware::class,
            'session.timeout' => \App\Http\Middleware\SessionTimeout::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Redirect to login on 419 (expired CSRF/session) for web requests
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            if (!$request->expectsJson()) {
                return redirect()->route('login')->with('error', 'আপনার session শেষ হয়ে গেছে। আবার login করুন।');
            }
            return response()->json(['message' => 'Session expired', 'redirect' => '/login'], 419);
        });

        // Redirect to login on unauthenticated (401) for web requests
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if (!$request->expectsJson()) {
                return redirect()->route('login')->with('error', 'আপনার session শেষ হয়ে গেছে। আবার login করুন।');
            }
            return response()->json(['message' => 'Unauthenticated', 'redirect' => '/login'], 401);
        });
    })->create();
