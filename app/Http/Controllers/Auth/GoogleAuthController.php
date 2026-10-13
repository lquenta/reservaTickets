<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! $this->configured()) {
            return redirect()->route('login')->with('message', 'El ingreso con Google no está disponible.');
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        if (! $this->configured()) {
            return redirect()->route('login')->with('message', 'El ingreso con Google no está disponible.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()->route('login')->with('message', 'No se pudo completar el ingreso con Google. Intenta de nuevo.');
        }

        $email = strtolower(trim((string) $googleUser->getEmail()));
        $googleId = trim((string) $googleUser->getId());

        if ($email === '' || $googleId === '' || ! $this->emailIsVerified($googleUser)) {
            return redirect()->route('login')->with('message', 'Google no entregó un correo verificado. Usa el formulario o intenta con otra cuenta.');
        }

        $user = User::query()->where('google_id', $googleId)->first();

        if (! $user) {
            $user = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->where(function ($query) {
                    $query->where('is_guest', false)->orWhereNull('is_guest');
                })
                ->first();

            if ($user) {
                $user->google_id = $googleId;
                if (! $user->hasVerifiedEmail()) {
                    $user->email_verified_at = now();
                }
                $user->save();
            }
        }

        if (! $user) {
            $user = new User([
                'name' => $this->displayName($googleUser),
                'email' => $email,
                'google_id' => $googleId,
                'password' => Str::password(40),
                'role' => User::ROLE_USER,
                'provisioned_via' => User::PROVISIONED_VIA_GOOGLE,
                'is_guest' => false,
            ]);
            $user->email_verified_at = now();
            $user->save();
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        $default = $user->isAdmin()
            ? route('admin.dashboard', absolute: false)
            : route('events.index', absolute: false);

        return redirect()->intended($default);
    }

    private function configured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    private function emailIsVerified(SocialiteUser $googleUser): bool
    {
        $raw = method_exists($googleUser, 'getRaw') ? $googleUser->getRaw() : [];
        if (! is_array($raw)) {
            return true;
        }

        $flag = $raw['email_verified'] ?? $raw['verified_email'] ?? null;
        if ($flag === null) {
            return true;
        }

        return filter_var($flag, FILTER_VALIDATE_BOOLEAN);
    }

    private function displayName(SocialiteUser $googleUser): string
    {
        $name = trim((string) $googleUser->getName());

        return $name !== '' ? $name : 'Usuario Google';
    }
}
