<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Services\PhoneNormalizer;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'ci' => $request->validated('ci'),
            'phone' => PhoneNormalizer::normalize($request->validated('phone')) ?? $request->validated('phone'),
            'password' => $request->validated('password'),
            'role' => 'user',
        ]);

        event(new Registered($user));

        auth()->login($user);

        return redirect()->route('verification.notice');
    }
}
