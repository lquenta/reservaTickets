<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\MailConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
            'mail_driver' => ['required', 'string', 'in:log,smtp,mailgun,sendgrid,smtpkit'],
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
            'mail_smtpkit_api_key' => ['nullable', 'string', 'max:255'],
            'mail_smtpkit_api_url' => ['nullable', 'string', 'url', 'max:255'],
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
        }

        if ($driver === MailConfigService::DRIVER_SMTPKIT) {
            if ($request->filled('mail_smtpkit_api_key')) {
                Setting::set('mail_smtpkit_api_key', $request->input('mail_smtpkit_api_key'));
            }
            Setting::set('mail_smtpkit_api_url', $request->input('mail_smtpkit_api_url') ?: 'https://smtpkit.com/api/v1/send-email');
        }

        MailConfigService::applyToConfig();

        return redirect()->route('admin.mail-settings.index')->with('message', 'Configuración de correo guardada correctamente.');
    }
}
