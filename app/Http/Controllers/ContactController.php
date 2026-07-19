<?php

namespace App\Http\Controllers;

use App\Services\RecaptchaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    public function store(Request $request, RecaptchaService $recaptcha): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
            'g-recaptcha-response' => [config('services.recaptcha.secret_key') ? 'required' : 'nullable', 'string'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Introduce un correo válido.',
            'message.required' => 'El mensaje es obligatorio.',
            'g-recaptcha-response.required' => 'Debe completar la verificación de seguridad.',
        ]);

        if ($validator->fails()) {
            return redirect()->to(route('home').'#contacto')->withInput()->withErrors($validator);
        }

        $validated = $validator->validated();

        if (config('services.recaptcha.secret_key')
            && ! $recaptcha->verify($validated['g-recaptcha-response'], $request->ip())) {
            return redirect()->to(route('home').'#contacto')
                ->withInput($request->except('g-recaptcha-response'))
                ->withErrors([
                    'g-recaptcha-response' => 'La verificación de seguridad ha fallado. Intente de nuevo.',
                ]);
        }

        Log::info('Contact form submission', [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'message' => $validated['message'],
        ]);

        return redirect()->route('home')->withFragment('contacto')->with('message', 'Mensaje enviado. Te responderemos pronto.');
    }
}
