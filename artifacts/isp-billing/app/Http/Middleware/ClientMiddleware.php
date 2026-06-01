<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClientMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('client.login');
        }
        
        $user = auth()->user();
        if ($user->user_type !== 'client' && !$user->hasRole('client')) {
            return redirect()->route('client.login')
                ->with('error', 'Please login with your PPPoE credentials to access the client panel.');
        }

        return $next($request);
    }
}
