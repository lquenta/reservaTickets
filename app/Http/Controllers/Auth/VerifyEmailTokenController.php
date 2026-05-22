<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\EmailVerificationTokenService;
use Illuminate\Http\RedirectResponse;

class VerifyEmailTokenController extends Controller
{
    public function __invoke(string $token, EmailVerificationTokenService $tokens): RedirectResponse
    {
        $user = $tokens->verify($token);

        if ($user === null) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'El enlace de verificación no es válido o ha expirado. Solicita uno nuevo desde tu cuenta.']);
        }

        if (auth()->check() && auth()->id() === $user->id) {
            return redirect()->intended(route('home').'?verified=1');
        }

        return redirect()
            ->route('login')
            ->with('status', 'Tu correo ha sido verificado. Ya puedes iniciar sesión.');
    }
}
