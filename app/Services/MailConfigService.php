<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;

class MailConfigService
{
    public const DRIVER_LOG = 'log';
    public const DRIVER_SMTP = 'smtp';
    public const DRIVER_MAILGUN = 'mailgun';
    public const DRIVER_SENDGRID = 'sendgrid';
    public const DRIVER_SMTPKIT = 'smtpkit';

    public const DRIVERS = [
        self::DRIVER_LOG => 'Log (solo guardar en log, no enviar)',
        self::DRIVER_SMTP => 'SMTP (servidor propio)',
        self::DRIVER_MAILGUN => 'Mailgun (API)',
        self::DRIVER_SENDGRID => 'SendGrid (API)',
        self::DRIVER_SMTPKIT => 'SmtpKit (API)',
    ];

    public static function applyToConfig(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('settings')) {
            return;
        }

        $driver = Setting::get('mail_driver');
        if ($driver === null || $driver === '') {
            return;
        }

        Config::set('mail.default', $driver);
        Config::set('mail.from.address', Setting::get('mail_from_address') ?? config('mail.from.address'));
        Config::set('mail.from.name', Setting::get('mail_from_name') ?? config('mail.from.name'));

        switch ($driver) {
            case self::DRIVER_SMTP:
                Config::set('mail.mailers.smtp', [
                    'transport' => 'smtp',
                    'host' => Setting::get('mail_smtp_host') ?? config('mail.mailers.smtp.host'),
                    'port' => (int) (Setting::get('mail_smtp_port') ?? config('mail.mailers.smtp.port')),
                    'username' => Setting::get('mail_smtp_username'),
                    'password' => Setting::get('mail_smtp_password'),
                    'encryption' => Setting::get('mail_smtp_encryption'),
                    'timeout' => null,
                    'local_domain' => parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST),
                    'verify_peer' => filter_var(Setting::get('mail_smtp_verify_peer') ?? true, FILTER_VALIDATE_BOOL),
                ]);
                break;
            case self::DRIVER_MAILGUN:
                Config::set('mail.mailers.mailgun', [
                    'transport' => 'mailgun',
                    'api_key' => Setting::get('mail_mailgun_api_key') ?? '',
                    'domain' => Setting::get('mail_mailgun_domain') ?? '',
                    'endpoint' => Setting::get('mail_mailgun_endpoint') ?? 'https://api.mailgun.net',
                ]);
                break;
            case self::DRIVER_SENDGRID:
                Config::set('mail.mailers.sendgrid', [
                    'transport' => 'sendgrid',
                    'api_key' => Setting::get('mail_sendgrid_api_key') ?? '',
                    'verify_ssl' => true,
                ]);
                break;
            case self::DRIVER_SMTPKIT:
                Config::set('mail.mailers.smtpkit', [
                    'transport' => 'smtpkit',
                    'api_key' => Setting::get('mail_smtpkit_api_key') ?? '',
                    'api_url' => Setting::get('mail_smtpkit_api_url') ?? 'https://smtpkit.com/api/v1/send-email',
                    'verify_ssl' => true,
                ]);
                break;
        }
    }

    public static function getMailSettingsForAdmin(): array
    {
        $keys = [
            'mail_driver', 'mail_from_address', 'mail_from_name',
            'mail_smtp_host', 'mail_smtp_port', 'mail_smtp_username', 'mail_smtp_password',
            'mail_smtp_encryption', 'mail_smtp_verify_peer',
            'mail_mailgun_api_key', 'mail_mailgun_domain', 'mail_mailgun_endpoint',
            'mail_sendgrid_api_key', 'mail_smtpkit_api_key', 'mail_smtpkit_api_url',
        ];
        $values = Setting::getMany($keys);
        if (empty($values['mail_smtp_verify_peer'])) {
            $values['mail_smtp_verify_peer'] = '1';
        }
        if (empty($values['mail_smtp_port']) && ($values['mail_driver'] ?? '') === self::DRIVER_SMTP) {
            $values['mail_smtp_port'] = '587';
        }
        // No exponer claves/contraseñas en la vista
        foreach (['mail_smtp_password', 'mail_mailgun_api_key', 'mail_sendgrid_api_key', 'mail_smtpkit_api_key'] as $secret) {
            if (! empty($values[$secret])) {
                $values[$secret] = '***';
            }
        }
        return $values;
    }
}
