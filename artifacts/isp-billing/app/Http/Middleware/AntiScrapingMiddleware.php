<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AntiScrapingMiddleware
{
    // Bot user agents to block
    private array $blockedAgents = [
        'scrapy', 'crawler', 'spider', 'wget', 'curl', 'python-requests',
        'python-urllib', 'java/', 'go-http', 'ruby', 'perl/', 'libwww',
        'zgrab', 'masscan', 'nikto', 'sqlmap', 'nmap', 'dirbuster',
        'hydra', 'medusa', 'burpsuite', 'havij', 'acunetix', 'nessus',
        'openvas', 'w3af', 'httping', 'httrack', 'wget/', 'curl/',
    ];

    // Suspicious URL patterns
    private array $blockedPaths = [
        '/.git', '/.env', '/wp-admin', '/wp-login', '/phpMyAdmin',
        '/phpmyadmin', '/.htaccess', '/dump', '/db.',
        '/config.', '/composer.json', '/composer.lock', '/.ssh',
        '/proc/', '/etc/passwd', '/etc/shadow', '/bin/bash',
        '/eval(', '/base64', '/shell', '/cmd.php', '/admin.php',
        '/test.php', '/info.php', '/phpinfo', '/../', '/../../',
    ];

    public function handle(Request $request, Closure $next)
    {
        // Allow all API v1 requests (N8N, Telegram bot, curl, python-requests etc.)
        if (str_starts_with($request->path(), "api/v1") || str_starts_with($request->path(), "api/mobile")) {
            return $next($request);
        }

        $ip = $request->ip();
        $userAgent = strtolower($request->userAgent() ?? '');
        $path = strtolower($request->path());
        $fullUrl = strtolower($request->fullUrl());

        // 1. Block known bad user agents
        foreach ($this->blockedAgents as $bot) {
            if (str_contains($userAgent, $bot)) {
                Log::warning("BLOCKED BOT UA: $ip | $userAgent");
                return response('Access Denied', 403);
            }
        }

        // 2. Block suspicious paths
        foreach ($this->blockedPaths as $badPath) {
            if (str_contains($fullUrl, $badPath)) {
                Log::warning("BLOCKED PATH: $ip | $fullUrl");
                return response('Not Found', 404);
            }
        }

        // 3. Empty user agent = likely bot
        if (empty(trim($userAgent)) || strlen($userAgent) < 10) {
            Log::warning("BLOCKED EMPTY UA: $ip");
            return response('Access Denied', 403);
        }

        // 4. IP-based rate limiting DISABLED
        // Note: This middleware runs as global middleware, BEFORE StartSession,
        // so auth()->check() always returns false (session not loaded yet).
        // Rate limiting is therefore incorrectly applied to all users — removed.

        // 5. Block requests with SQL injection patterns
        $queryString = strtolower($request->getQueryString() ?? '');
        $sqlPatterns = ["'--", 'union select', 'drop table', 'insert into',
                        'delete from', 'exec(', 'execute(', 'xp_cmdshell',
                        '1=1', 'or 1=1', 'and 1=1', '/*', '*/', 'char('];
        foreach ($sqlPatterns as $pattern) {
            if (str_contains($queryString, $pattern)) {
                Log::warning("SQL INJECTION ATTEMPT: $ip | $queryString");
                return response('Bad Request', 400);
            }
        }

        // 6. Add security headers to response
        $response = $next($request);

        if (method_exists($response, 'header')) {
            $response->header('X-Content-Type-Options', 'nosniff');
            $response->header('X-Frame-Options', 'SAMEORIGIN');
            $response->header('X-XSS-Protection', '1; mode=block');
            $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->header('X-Powered-By', ''); // Hide PHP version
            $response->header('Server', 'SOFTYNEX'); // Hide nginx version
        }

        return $response;
    }
}
