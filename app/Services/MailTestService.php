<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;

class MailTestService
{
    public function send(string $to): void
    {
        $mailer = config('mail.default');

        Mail::raw(
            "Este es un correo de prueba enviado desde Laravel.\n\nMailer: {$mailer}\nFecha: ".now()->toDateTimeString(),
            function ($message) use ($to) {
                $message->to($to)
                    ->subject('Prueba de correo - '.config('app.name'));
            }
        );
    }
}
