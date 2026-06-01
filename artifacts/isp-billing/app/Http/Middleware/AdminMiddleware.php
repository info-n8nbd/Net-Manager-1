<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        
        $user = auth()->user();

        if (in_array($user->user_type, ['super_admin', 'admin'])) {
            return $next($request);
        }

        if ($user->user_type === 'employee' && $user->roles->isNotEmpty()) {
            return $next($request);
        }

        if ($user->roles->isNotEmpty()) {
            $excludedTypes = ['client', 'reseller', 'sub_reseller', 'card_distributor'];
            if (!in_array($user->user_type, $excludedTypes)) {
                return $next($request);
            }
        }

        abort(403, 'Unauthorized access.');
    }
}
