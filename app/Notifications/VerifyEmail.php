<?php

namespace App\Notifications;

use App\Services\EmailVerificationTokenService;
use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailBase;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmail extends VerifyEmailBase
{
    /**
     * @param  \App\Models\User  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $url = app(EmailVerificationTokenService::class)->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verifica tu correo — NOVA Tickets')
            ->view('emails.verify-email', [
                'url' => $url,
                'loginUrl' => route('login'),
                'user' => $notifiable,
            ]);
    }
}
