<?php

namespace App\Http\Middleware;

use Closure;

class AuthenticateToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
return response()->json([
    'hello' => "there"
], 401)->header('Content-Type', 'application/json');
        return $next($request);
    }
}