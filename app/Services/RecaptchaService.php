<?php

namespace App\Services;

use ReCaptcha\ReCaptcha;

class RecaptchaService
{
    public function verify(string $token, ?string $remoteIp = null): bool
    {
        $secret = config('services.recaptcha.secret_key');
        if (empty($secret)) {
            return true; // Skip verification when not configured (e.g. local)
        }

        $recaptcha = new ReCaptcha($secret);
        $response = $recaptcha->setExpectedHostname(request()->getHost())
            ->verify($token, $remoteIp ?? request()->ip());

        return $response->isSuccess();
    }
}
