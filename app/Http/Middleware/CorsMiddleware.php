<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->getMethod() === "OPTIONS") {
        $response = response()->json([], 200);
    } else {
        $response = $next($request);
    }

    $response->headers->set('Access-Control-Allow-Origin', '*');
    $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, Authorization');
    $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');

    return $response;
    }
}
