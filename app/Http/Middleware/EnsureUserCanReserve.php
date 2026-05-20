<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanReserve
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user?->isAdmin()) {
            return redirect()->route('admin.dashboard')
                ->with('message', 'Los administradores no pueden reservar tickets.');
        }
        if ($user?->isVendedor()) {
            return redirect()->route('seller.events.index')
                ->with('message', 'Usa la sección Vender tickets para comprar a nombre de un cliente.');
        }

        return $next($request);
    }
}
