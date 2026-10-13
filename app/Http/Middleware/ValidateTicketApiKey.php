<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateTicketApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('services.ticket_validator.enabled', true)) {
            return response()->json([
                'valid' => false,
                'message' => 'API de validación deshabilitada en el servidor.',
            ], 503);
        }

        $apiKey = config('services.ticket_validator.api_key');
        if (empty($apiKey)) {
            return response()->json(['message' => 'API key not configured.'], 503);
        }

        $provided = $request->header('X-API-Key')
            ?? ($request->bearerToken() ?: null);
        if (empty($provided) || ! hash_equals($apiKey, $provided)) {
            return response()->json(['message' => 'Invalid or missing API key.'], 401);
        }

        return $next($request);
    }
}
