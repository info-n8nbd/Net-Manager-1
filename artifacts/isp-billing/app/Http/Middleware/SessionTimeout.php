<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Models\SessionLog;

class SessionTimeout
{
    protected int $timeout = 1800;

    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        if ($request->routeIs('session.keepalive') || $request->routeIs('logout')) {
            return $next($request);
        }

        $lastActivity = session('last_activity_time');

        if ($lastActivity && (time() - $lastActivity) > $this->timeout) {
            // Mark session as expired in session_logs
            try {
                SessionLog::logLogout($request->session()->getId(), 'session_expired');
            } catch (\Exception $e) {}

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Session expired. Please login again.'], 401);
            }

            return redirect()->route('login')->with('warning', 'আপনার session ৩০ মিনিট নিষ্ক্রিয়তার কারণে স্বয়ংক্রিয়ভাবে শেষ হয়ে গেছে।');
        }

        session(['last_activity_time' => time()]);

        // Track active session — create if missing, update last_activity every 60 seconds
        try {
            $sessionId = $request->session()->getId();
            $userId    = Auth::id();
            $cacheKey  = 'session_tracked_' . $sessionId;

            if (!session($cacheKey)) {
                $existing = SessionLog::where('session_id', $sessionId)
                    ->where('is_active', true)
                    ->first();

                if (!$existing) {
                    // Create new session log (handles already-logged-in users)
                    $ua         = $request->userAgent();
                    $deviceType = 'desktop';
                    if ($ua && (stripos($ua, 'mobile') !== false || stripos($ua, 'android') !== false)) {
                        $deviceType = 'mobile';
                    } elseif ($ua && (stripos($ua, 'tablet') !== false || stripos($ua, 'ipad') !== false)) {
                        $deviceType = 'tablet';
                    }

                    SessionLog::create([
                        'user_id'          => $userId,
                        'session_id'       => $sessionId,
                        'ip_address'       => $request->ip(),
                        'user_agent'       => $ua,
                        'device_type'      => $deviceType,
                        'login_at'         => now(),
                        'last_activity_at' => now(),
                        'is_active'        => true,
                    ]);
                } else {
                    $existing->update(['last_activity_at' => now()]);
                }

                session([$cacheKey => time()]);
            } elseif ((time() - session($cacheKey)) > 60) {
                // Update last_activity every 60 seconds
                SessionLog::where('session_id', $sessionId)
                    ->where('is_active', true)
                    ->update(['last_activity_at' => now()]);

                session([$cacheKey => time()]);
            }
        } catch (\Exception $e) {}

        return $next($request);
    }
}
