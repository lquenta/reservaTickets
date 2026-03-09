<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationSettingsController extends Controller
{
    public function index(): View
    {
        $settings = [
            'telegram_enabled' => filter_var(Setting::get('telegram_enabled'), FILTER_VALIDATE_BOOL),
            'telegram_bot_token' => Setting::get('telegram_bot_token') ? '***' : '',
            'telegram_chat_id' => Setting::get('telegram_chat_id'),
        ];
        return view('admin.notification-settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'telegram_enabled' => ['nullable', 'boolean'],
            'telegram_bot_token' => ['nullable', 'string', 'max:255'],
            'telegram_chat_id' => ['nullable', 'string', 'max:100'],
        ]);

        Setting::set('telegram_enabled', $request->boolean('telegram_enabled') ? '1' : '0');
        Setting::set('telegram_chat_id', $request->input('telegram_chat_id') ?? '');

        if ($request->filled('telegram_bot_token')) {
            Setting::set('telegram_bot_token', $request->input('telegram_bot_token'));
        }

        return redirect()->route('admin.notification-settings.index')
            ->with('message', 'Configuración de notificaciones guardada.');
    }
}
