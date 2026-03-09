@extends('layouts.admin')

@section('title', 'Notificaciones - Admin')

@section('admin')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Notificaciones a celular</h1>
    <p class="text-slate-600 dark:text-slate-400 mt-1">Recibe un aviso por Telegram cuando un cliente suba el comprobante y quede una reserva pendiente de revisión.</p>
</div>

<div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 p-6 shadow-lg">
    <form action="{{ route('admin.notification-settings.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="space-y-4 p-4 rounded-xl bg-slate-100 dark:bg-slate-700/30 border border-slate-200 dark:border-slate-600">
            <h2 class="text-lg font-semibold text-slate-800 dark:text-white">Telegram</h2>

            <div class="flex items-center gap-3">
                <input type="hidden" name="telegram_enabled" value="0">
                <input type="checkbox" name="telegram_enabled" id="telegram_enabled" value="1" {{ old('telegram_enabled', $settings['telegram_enabled'] ?? false) ? 'checked' : '' }}
                    class="rounded border-slate-300 dark:border-slate-600 text-violet-600 focus:ring-violet-500">
                <label for="telegram_enabled" class="text-sm font-medium text-slate-700 dark:text-slate-300">Activar notificaciones por Telegram</label>
            </div>

            <div>
                <label for="telegram_bot_token" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Token del bot</label>
                <input type="password" name="telegram_bot_token" id="telegram_bot_token" value="" placeholder="{{ $settings['telegram_bot_token'] === '***' ? 'Configurado (dejar en blanco para no cambiar)' : 'Ej: 123456:ABC-DEF...' }}" autocomplete="off"
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-3 focus:ring-2 focus:ring-violet-500">
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Crea un bot con <a href="https://t.me/BotFather" target="_blank" rel="noopener" class="text-violet-600 hover:underline">@BotFather</a> en Telegram y pega aquí el token.</p>
                @error('telegram_bot_token')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="telegram_chat_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Chat ID</label>
                <input type="text" name="telegram_chat_id" id="telegram_chat_id" value="{{ old('telegram_chat_id', $settings['telegram_chat_id'] ?? '') }}" placeholder="Ej: 123456789 o -1001234567890"
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-3 focus:ring-2 focus:ring-violet-500">
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Envía un mensaje a tu bot y obtén tu chat_id con <a href="https://api.telegram.org/botTU_TOKEN/getUpdates" target="_blank" rel="noopener" class="text-violet-600 hover:underline">getUpdates</a> (sustituye TU_TOKEN) o usa un bot que lo muestre (ej. @userinfobot para tu id de usuario).</p>
                @error('telegram_chat_id')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
        </div>

        <button type="submit" class="rounded-xl bg-violet-600 hover:bg-violet-500 px-5 py-2.5 text-white font-semibold transition">
            Guardar
        </button>
    </form>
</div>
@endsection
