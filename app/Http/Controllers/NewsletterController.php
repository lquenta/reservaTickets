<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class NewsletterController extends Controller
{
    public function subscribe(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:255'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Introduce un correo electrónico válido.',
        ]);

        if ($validator->fails()) {
            return redirect()->to(route('home') . '#boletin')->withInput()->withErrors($validator);
        }

        $validated = $validator->validated();

        $existing = NewsletterSubscriber::where('email', $validated['email'])->first();

        if ($existing) {
            return redirect()->route('home')->withFragment('boletin')->with('message', 'Ya estás suscrito a nuestro boletín.');
        }

        NewsletterSubscriber::create([
            'email' => $validated['email'],
            'unsubscribe_token' => Str::random(64),
        ]);

        return redirect()->route('home')->withFragment('boletin')->with('message', '¡Gracias! Te has suscrito al boletín correctamente.');
    }
}
