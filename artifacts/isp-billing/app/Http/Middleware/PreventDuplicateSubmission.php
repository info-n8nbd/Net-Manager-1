<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PreventDuplicateSubmission
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next($request);
        }

        $userId = auth()->id() ?? 'guest';
        $routeName = $request->route()?->getName() ?? $request->path();
        // Include full URL path + route params so same route with different IDs are treated as distinct
        $routeParams = $request->route() ? json_encode($request->route()->parameters()) : '';
        $requestHash = md5($userId . $routeName . $routeParams . $request->getPathInfo() . json_encode($request->except(['_token', '_method'])));
        $cacheKey = "duplicate_submission:{$requestHash}";

        if (Cache::has($cacheKey)) {
            Log::warning('Duplicate submission blocked', [
                'user_id' => $userId,
                'route' => $routeName,
                'ip' => $request->ip(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Duplicate request detected. Please wait before resubmitting.',
                ], 429);
            }

            return back()->with('warning', 'Request already processing. Please wait.');
        }

        Cache::put($cacheKey, true, now()->addSeconds(5));

        return $next($request);
    }
}
