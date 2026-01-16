<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $allowedOrigin = 'https://homady.vercel.app'; // ضع دومين فرونتند الصحيح

        $headers = [
            'Access-Control-Allow-Origin' => $allowedOrigin,
            'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS, PUT, DELETE',
            'Access-Control-Allow-Headers' => 'Content-Type, X-Requested-With, Authorization',
            'Access-Control-Allow-Credentials' => 'true'
        ];

        // Preflight request
        if ($request->isMethod('OPTIONS')) {
            return response()->json('OK', 200, $headers);
        }

        // طلبات عادية
        $response = $next($request);
        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }
}
