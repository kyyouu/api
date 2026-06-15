<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-KEY') ?? $request->query('api_key');

        if ($apiKey !== 'trusmi') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. API Key salah atau tidak ada.',
            ], 401);
        }

        return $next($request);

        $apiKey = $request->header('X-API-KEY') ?? $request->query('api_key');
    }
}