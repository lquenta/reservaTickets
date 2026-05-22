<?php

namespace App\Services;

use App\Models\EmailVerificationToken;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Str;

class EmailVerificationTokenService
{
    public const TTL_MINUTES = 60;

    public function issue(User $user): string
    {
        EmailVerificationToken::query()
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->delete();

        $plain = Str::random(64);

        EmailVerificationToken::create([
            'user_id' => $user->id,
            'token_hash' => $this->hash($plain),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        return $plain;
    }

    public function verify(string $plain): ?User
    {
        $token = EmailVerificationToken::query()
            ->where('token_hash', $this->hash($plain))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($token === null) {
            return null;
        }

        $token->update(['used_at' => now()]);

        $user = $token->user;

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $user;
    }

    public function verificationUrl(User $user): string
    {
        return route('verification.verify-token', [
            'token' => $this->issue($user),
        ]);
    }

    private function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }
}
