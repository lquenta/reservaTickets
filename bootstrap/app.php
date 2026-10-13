<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'seller' => \App\Http\Middleware\EnsureUserIsSeller::class,
            'can.reserve' => \App\Http\Middleware\EnsureUserCanReserve::class,
            'ticket.api.key' => \App\Http\Middleware\ValidateTicketApiKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if (! $request->is('login', 'register', 'forgot-password', 'reset-password', 'reset-password/*')) {
                return null;
            }

            $message = 'Demasiados intentos. Espera un minuto e inténtalo de nuevo.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 429);
            }

            return redirect()->back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('message', $message);
        });
    })->create();
