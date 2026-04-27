@extends('layouts.admin')

@section('title', 'Correo / SMTP - Admin')

@section('admin')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Configuración de correo</h1>
    <p class="text-slate-600 dark:text-slate-400 mt-1">SMTP y APIs de envío de correo. Si no configuras nada aquí, se usan los valores del archivo .env.</p>
</div>

<div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-6 shadow-lg">
    <form action="{{ route('admin.mail-settings.update') }}" method="POST" class="space-y-6" x-data="{ driver: '{{ old('mail_driver', $settings['mail_driver'] ?? 'log') }}' }">
        @csrf
        @method('PUT')

        <div>
            <label for="mail_driver" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Método de envío</label>
            <select name="mail_driver" id="mail_driver" x-model="driver" required
                class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-3 focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
                @foreach($drivers as $value => $label)
                    <option value="{{ $value }}" {{ old('mail_driver', $settings['mail_driver'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('mail_driver')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="mail_from_address" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Correo remitente</label>
                <input type="email" name="mail_from_address" id="mail_from_address" value="{{ old('mail_from_address', $settings['mail_from_address'] ?? config('mail.from.address')) }}" required
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-3 focus:ring-2 focus:ring-violet-500">
                @error('mail_from_address')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="mail_from_name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nombre remitente</label>
                <input type="text" name="mail_from_name" id="mail_from_name" value="{{ old('mail_from_name', $settings['mail_from_name'] ?? config('mail.from.name')) }}" required maxlength="255"
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-3 focus:ring-2 focus:ring-violet-500">
                @error('mail_from_name')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- SMTP --}}
        <div x-show="driver === 'smtp'" x-cloak class="space-y-4 p-4 rounded-xl bg-slate-100 dark:bg-slate-700/30 border border-slate-200 dark:border-slate-600">
            <h2 class="text-lg font-semibold text-slate-800 dark:text-white">SMTP</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="mail_smtp_host" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Host</label>
                    <input type="text" name="mail_smtp_host" id="mail_smtp_host" value="{{ old('mail_smtp_host', $settings['mail_smtp_host'] ?? '') }}" placeholder="smtp.ejemplo.com"
                        class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-3 focus:ring-2 focus:ring-violet-500">
                    @error('mail_smtp_host')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="mail_smtp_port" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Puerto</label>
                    <input type="number" name="mail_smtp_port" id="mail_smtp_port" value="{{ old('mail_smtp_port', $settings['mail_smtp_port'] ?? '587') }}" min="1" max="65535" placeholder="587"
                        class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-3 focus:ring-2 focus:ring-violet-500">
                    @error('mail_smtp_port')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="mail_smtp_username" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Usuario</label>
                    <input type="text" name="mail_smtp_username" id="mail_smtp_username" value="{{ old('mail_smtp_username', $settings['mail_smtp_username'] ?? '') }}"
                        class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-3 focus:ring-2 focus:ring-violet-500">
                    @error('mail_smtp_username')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="mail_smtp_password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Contraseña</label>
                    <input type="password" name="mail_smtp_password" id="mail_smtp_password" value="" placeholder="{{ ($settings['mail_smtp_password'] ?? '') === '***' ? 'Dejar en blanco para no cambiar' : '' }}" autocomplete="new-password"
                        class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-3 focus:ring-2 focus:ring-violet-500">
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Dejar en blanco para no cambiar la actual.</p>
                    @error('mail_smtp_password')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="flex flex-wrap gap-4 items-center">
                <div>
                    <label for="mail_smtp_encryption" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Cifrado</label>
                    <select name="mail_smtp_encryption" id="mail_smtp_encryption"
                        class="rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-3 focus:ring-2 focus:ring-violet-500">
                        <option value="" {{ old('mail_smtp_encryption', $settings['mail_smtp_encryption'] ?? '') === '' ? 'selected' : '' }}>Ninguno</option>
                        <option value="tls" {{ old('mail_smtp_encryption', $settings['mail_smtp_encryption'] ?? '') === 'tls' ? 'selected' : '' }}>TLS</option>
                        <option value="ssl" {{ old('mail_smtp_encryption', $settings['mail_smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                    </select>
                </div>
                <div class="flex items-center gap-2 pt-8">
                    <input type="hidden" name="mail_smtp_verify_peer" value="0">
                    <input type="checkbox" name="mail_smtp_verify_peer" id="mail_smtp_verify_peer" value="1" {{ old('mail_smtp_verify_peer', $settings['mail_smtp_verify_peer'] ?? '1') ? 'checked' : '' }}
                        class="rounded border-slate-300 dark:border-slate-600 text-violet-600 focus:ring-violet-500">
                    <label for="mail_smtp_verify_peer" class="text-sm text-slate-700 dark:text-slate-300">Verificar certificado SSL</label>
                </div>
            </div>
        </div>

        {{-- Mailgun --}}
        <div x-show="driver === 'mailgun'" x-cloak class="space-y-4 p-4 rounded-xl bg-slate-100 dark:bg-slate-700/30 border border-slate-200 dark:border-slate-600">
            <h2 class="text-lg font-semibold text-slate-800 dark:text-white">Mailgun</h2>
            <div>
                <label for="mail_mailgun_api_key" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">API Key</label>
                <input type="password" name="mail_mailgun_api_key" id="mail_mailgun_api_key" value="{{ old('mail_mailgun_api_key') }}" placeholder="{{ ($settings['mail_mailgun_api_key'] ?? '') === '***' ? 'Configurado (dejar en blanco para no cambiar)' : '' }}" autocomplete="off"
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-3 focus:ring-2 focus:ring-violet-500">
                @error('mail_mailgun_api_key')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="mail_mailgun_domain" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Dominio</label>
                    <input type="text" name="mail_mailgun_domain" id="mail_mailgun_domain" value="{{ old('mail_mailgun_domain', $settings['mail_mailgun_domain'] ?? '') }}" placeholder="mg.ejemplo.com"
                        class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-3 focus:ring-2 focus:ring-violet-500">
                    @error('mail_mailgun_domain')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="mail_mailgun_endpoint" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Endpoint (opcional)</label>
                    <input type="url" name="mail_mailgun_endpoint" id="mail_mailgun_endpoint" value="{{ old('mail_mailgun_endpoint', $settings['mail_mailgun_endpoint'] ?? 'https://api.mailgun.net') }}" placeholder="https://api.mailgun.net"
                        class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-3 focus:ring-2 focus:ring-violet-500">
                    @error('mail_mailgun_endpoint')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- SendGrid --}}
        <div x-show="driver === 'sendgrid'" x-cloak class="space-y-4 p-4 rounded-xl bg-slate-100 dark:bg-slate-700/30 border border-slate-200 dark:border-slate-600">
            <h2 class="text-lg font-semibold text-slate-800 dark:text-white">SendGrid</h2>
            <div>
                <label for="mail_sendgrid_api_key" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">API Key</label>
                <input type="password" name="mail_sendgrid_api_key" id="mail_sendgrid_api_key" value="{{ old('mail_sendgrid_api_key') }}" placeholder="{{ ($settings['mail_sendgrid_api_key'] ?? '') === '***' ? 'Configurado (dejar en blanco para no cambiar)' : '' }}" autocomplete="off"
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-3 focus:ring-2 focus:ring-violet-500">
                @error('mail_sendgrid_api_key')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="mail_sendgrid_verify_ssl" value="0">
                <input type="checkbox" name="mail_sendgrid_verify_ssl" id="mail_sendgrid_verify_ssl" value="1" {{ old('mail_sendgrid_verify_ssl', $settings['mail_sendgrid_verify_ssl'] ?? '1') ? 'checked' : '' }}
                    class="rounded border-slate-300 dark:border-slate-600 text-violet-600 focus:ring-violet-500">
                <label for="mail_sendgrid_verify_ssl" class="text-sm text-slate-700 dark:text-slate-300">Verificar certificado SSL</label>
            </div>
        </div>

        {{-- SmtpKit --}}
        <div x-show="driver === 'smtpkit'" x-cloak class="space-y-4 p-4 rounded-xl bg-slate-100 dark:bg-slate-700/30 border border-slate-200 dark:border-slate-600">
            <h2 class="text-lg font-semibold text-slate-800 dark:text-white">SmtpKit</h2>
            <div>
                <label for="mail_smtpkit_api_key" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">API Key</label>
                <input type="password" name="mail_smtpkit_api_key" id="mail_smtpkit_api_key" value="{{ old('mail_smtpkit_api_key') }}" placeholder="{{ ($settings['mail_smtpkit_api_key'] ?? '') === '***' ? 'Configurado (dejar en blanco para no cambiar)' : '' }}" autocomplete="off"
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-3 focus:ring-2 focus:ring-violet-500">
                @error('mail_smtpkit_api_key')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="mail_smtpkit_api_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">URL API (opcional)</label>
                <input type="url" name="mail_smtpkit_api_url" id="mail_smtpkit_api_url" value="{{ old('mail_smtpkit_api_url', $settings['mail_smtpkit_api_url'] ?? 'https://smtpkit.com/api/v1/send-email') }}"
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-3 focus:ring-2 focus:ring-violet-500">
                @error('mail_smtpkit_api_url')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="mail_smtpkit_verify_ssl" value="0">
                <input type="checkbox" name="mail_smtpkit_verify_ssl" id="mail_smtpkit_verify_ssl" value="1" {{ old('mail_smtpkit_verify_ssl', $settings['mail_smtpkit_verify_ssl'] ?? '1') ? 'checked' : '' }}
                    class="rounded border-slate-300 dark:border-slate-600 text-violet-600 focus:ring-violet-500">
                <label for="mail_smtpkit_verify_ssl" class="text-sm text-slate-700 dark:text-slate-300">Verificar certificado SSL</label>
            </div>
        </div>

        {{-- Brevo --}}
        <div x-show="driver === 'brevo'" x-cloak class="space-y-4 p-4 rounded-xl bg-slate-100 dark:bg-slate-700/30 border border-slate-200 dark:border-slate-600">
            <h2 class="text-lg font-semibold text-slate-800 dark:text-white">Brevo</h2>
            <div>
                <label for="mail_brevo_api_key" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">API Key</label>
                <input type="password" name="mail_brevo_api_key" id="mail_brevo_api_key" value="{{ old('mail_brevo_api_key') }}" placeholder="{{ ($settings['mail_brevo_api_key'] ?? '') === '***' ? 'Configurado (dejar en blanco para no cambiar)' : '' }}" autocomplete="off"
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-3 focus:ring-2 focus:ring-violet-500">
                @error('mail_brevo_api_key')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="mail_brevo_api_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">URL API (opcional)</label>
                <input type="url" name="mail_brevo_api_url" id="mail_brevo_api_url" value="{{ old('mail_brevo_api_url', $settings['mail_brevo_api_url'] ?? 'https://api.brevo.com/v3/smtp/email') }}"
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-3 focus:ring-2 focus:ring-violet-500">
                @error('mail_brevo_api_url')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="mail_brevo_verify_ssl" value="0">
                <input type="checkbox" name="mail_brevo_verify_ssl" id="mail_brevo_verify_ssl" value="1" {{ old('mail_brevo_verify_ssl', $settings['mail_brevo_verify_ssl'] ?? '1') ? 'checked' : '' }}
                    class="rounded border-slate-300 dark:border-slate-600 text-violet-600 focus:ring-violet-500">
                <label for="mail_brevo_verify_ssl" class="text-sm text-slate-700 dark:text-slate-300">Verificar certificado SSL</label>
            </div>
        </div>

        <div class="pt-4">
            <button type="submit" class="rounded-xl bg-violet-600 hover:bg-violet-500 px-5 py-2.5 text-white font-semibold transition">
                Guardar configuración
            </button>
        </div>
    </form>

    <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-700">
        <h2 class="text-lg font-semibold text-slate-800 dark:text-white mb-2">Enviar correo de prueba</h2>
        <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">Usa la configuración activa para enviar un correo de prueba y validar la integración.</p>
        <form action="{{ route('admin.mail-settings.send-test') }}" method="POST" class="flex flex-col md:flex-row gap-3 md:items-end">
            @csrf
            <div class="flex-1">
                <label for="mail_test_to" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Destino de prueba</label>
                <input type="email" name="mail_test_to" id="mail_test_to" required value="{{ old('mail_test_to', $settings['mail_from_address'] ?? config('mail.from.address')) }}"
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-3 focus:ring-2 focus:ring-violet-500"
                    placeholder="correo@ejemplo.com">
                @error('mail_test_to')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <button type="submit" class="rounded-xl bg-slate-800 hover:bg-slate-700 dark:bg-slate-100 dark:hover:bg-white dark:text-slate-900 px-5 py-2.5 text-white font-semibold transition">
                    Enviar prueba
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
