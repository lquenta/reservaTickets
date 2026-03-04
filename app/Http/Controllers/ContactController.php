<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Introduce un correo válido.',
            'message.required' => 'El mensaje es obligatorio.',
        ]);

        if ($validator->fails()) {
            return redirect()->to(route('home') . '#contacto')->withInput()->withErrors($validator);
        }

        $validated = $validator->validated();

        Log::info('Contact form submission', [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'message' => $validated['message'],
        ]);

        return redirect()->route('home')->withFragment('contacto')->with('message', 'Mensaje enviado. Te responderemos pronto.');
    }
}
