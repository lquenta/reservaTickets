<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\MailConfigService;
use App\Services\MailTestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Mail;

class MailSettingsController extends Controller
{
    public function index(): View
    {
        $settings = MailConfigService::getMailSettingsForAdmin();
        return view('admin.mail-settings.index', [
            'settings' => $settings,
            'drivers' => MailConfigService::DRIVERS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'mail_driver' => ['required', 'string', 'in:log,smtp,mailgun,sendgrid,smtpkit,brevo'],
            'mail_from_address' => ['required', 'email'],
            'mail_from_name' => ['required', 'string', 'max:255'],
            'mail_smtp_host' => ['nullable', 'string', 'max:255'],
            'mail_smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_smtp_username' => ['nullable', 'string', 'max:255'],
            'mail_smtp_password' => ['nullable', 'string', 'max:255'],
            'mail_smtp_encryption' => ['nullable', 'string', 'in:tls,ssl,'],
            'mail_smtp_verify_peer' => ['nullable', 'boolean'],
            'mail_mailgun_api_key' => ['nullable', 'string', 'max:255'],
            'mail_mailgun_domain' => ['nullable', 'string', 'max:255'],
            'mail_mailgun_endpoint' => ['nullable', 'string', 'url', 'max:255'],
            'mail_sendgrid_api_key' => ['nullable', 'string', 'max:255'],
            'mail_sendgrid_verify_ssl' => ['nullable', 'boolean'],
            'mail_smtpkit_api_key' => ['nullable', 'string', 'max:255'],
            'mail_smtpkit_api_url' => ['nullable', 'string', 'url', 'max:255'],
            'mail_smtpkit_verify_ssl' => ['nullable', 'boolean'],
            'mail_brevo_api_key' => ['nullable', 'string', 'max:255'],
            'mail_brevo_api_url' => ['nullable', 'string', 'url', 'max:255'],
            'mail_brevo_verify_ssl' => ['nullable', 'boolean'],
        ]);

        $driver = $request->input('mail_driver');
        Setting::set('mail_driver', $driver);
        Setting::set('mail_from_address', $request->input('mail_from_address'));
        Setting::set('mail_from_name', $request->input('mail_from_name'));

        if ($driver === MailConfigService::DRIVER_SMTP) {
            Setting::set('mail_smtp_host', $request->input('mail_smtp_host'));
            Setting::set('mail_smtp_port', $request->input('mail_smtp_port'));
            Setting::set('mail_smtp_username', $request->input('mail_smtp_username'));
            if ($request->filled('mail_smtp_password')) {
                Setting::set('mail_smtp_password', $request->input('mail_smtp_password'));
            }
            Setting::set('mail_smtp_encryption', $request->input('mail_smtp_encryption') ?: null);
            Setting::set('mail_smtp_verify_peer', $request->boolean('mail_smtp_verify_peer') ? '1' : '0');
        }

        if ($driver === MailConfigService::DRIVER_MAILGUN) {
            if ($request->filled('mail_mailgun_api_key')) {
                Setting::set('mail_mailgun_api_key', $request->input('mail_mailgun_api_key'));
            }
            Setting::set('mail_mailgun_domain', $request->input('mail_mailgun_domain'));
            Setting::set('mail_mailgun_endpoint', $request->input('mail_mailgun_endpoint') ?: 'https://api.mailgun.net');
        }

        if ($driver === MailConfigService::DRIVER_SENDGRID) {
            if ($request->filled('mail_sendgrid_api_key')) {
                Setting::set('mail_sendgrid_api_key', $request->input('mail_sendgrid_api_key'));
            }
            Setting::set('mail_sendgrid_verify_ssl', $request->boolean('mail_sendgrid_verify_ssl') ? '1' : '0');
        }

        if ($driver === MailConfigService::DRIVER_SMTPKIT) {
            if ($request->filled('mail_smtpkit_api_key')) {
                Setting::set('mail_smtpkit_api_key', $request->input('mail_smtpkit_api_key'));
            }
            Setting::set('mail_smtpkit_api_url', $request->input('mail_smtpkit_api_url') ?: 'https://smtpkit.com/api/v1/send-email');
            Setting::set('mail_smtpkit_verify_ssl', $request->boolean('mail_smtpkit_verify_ssl') ? '1' : '0');
        }

        if ($driver === MailConfigService::DRIVER_BREVO) {
            if ($request->filled('mail_brevo_api_key')) {
                Setting::set('mail_brevo_api_key', $request->input('mail_brevo_api_key'));
            }
            Setting::set('mail_brevo_api_url', $request->input('mail_brevo_api_url') ?: 'https://api.brevo.com/v3/smtp/email');
            Setting::set('mail_brevo_verify_ssl', $request->boolean('mail_brevo_verify_ssl') ? '1' : '0');
        }

        MailConfigService::applyToConfig();
        Mail::purge(); // forzar uso del nuevo driver en la misma petición / cola

        return redirect()->route('admin.mail-settings.index')->with('message', 'Configuración de correo guardada correctamente.');
    }

    public function sendTest(Request $request, MailTestService $mailTestService): RedirectResponse
    {
        $data = $request->validate([
            'mail_test_to' => ['required', 'email'],
        ]);

        MailConfigService::applyToConfig();
        Mail::purge();

        try {
            $mailTestService->send($data['mail_test_to']);

            return redirect()
                ->route('admin.mail-settings.index')
                ->with('message', 'Correo de prueba enviado correctamente a '.$data['mail_test_to'].'.');
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.mail-settings.index')
                ->withInput(['mail_test_to' => $data['mail_test_to']])
                ->withErrors(['mail_test_to' => 'No se pudo enviar el correo de prueba: '.$e->getMessage()]);
        }
    }
}
