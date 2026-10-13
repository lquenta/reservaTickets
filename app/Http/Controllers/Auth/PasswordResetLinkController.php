<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Throwable;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser una dirección válida.',
        ]);

        try {
            $status = Password::sendResetLink([
                'email' => strtolower(trim((string) $request->input('email'))),
            ]);
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput($request->only('email'))
                ->with('message', 'No se pudo enviar el correo. Intenta de nuevo en unos minutos.');
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()
                ->withInput($request->only('email'))
                ->with('message', 'Espera un minuto antes de pedir otro enlace.');
        }

        return back()->with('message', 'Si el correo está registrado, te enviamos un enlace para elegir una contraseña nueva.');
    }
}
