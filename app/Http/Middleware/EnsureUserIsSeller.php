<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSeller
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isVendedor()) {
            abort(403, 'No autorizado. Solo vendedores pueden acceder a esta sección.');
        }

        return $next($request);
    }
}
