<?php

namespace App\Console\Commands;

use App\Services\MailTestService;
use Illuminate\Console\Command;

class TestMailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test
                            {--to= : Email del destinatario (por defecto MAIL_FROM_ADDRESS)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía un correo de prueba con el mailer configurado (útil para probar SmtpKit API u otro).';

    /**
     * Execute the console command.
     */
    public function handle(MailTestService $mailTestService): int
    {
        $to = $this->option('to') ?: config('mail.from.address');

        if (! $to || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error('Indica un email válido con --to=correo@ejemplo.com o configura MAIL_FROM_ADDRESS.');

            return self::FAILURE;
        }

        $mailer = config('mail.default');
        $this->info("Mailer actual: {$mailer}");
        $this->info("Enviando correo de prueba a: {$to}");

        try {
            $mailTestService->send($to);
            $this->info('Correo enviado correctamente. Revisa la bandeja (y spam) de '.$to);

            if ($mailer === 'log') {
                $this->comment('Con MAIL_MAILER=log el mensaje se escribe en storage/logs/laravel.log.');
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Error al enviar: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
