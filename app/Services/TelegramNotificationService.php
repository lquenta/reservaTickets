<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    private const API_BASE = 'https://api.telegram.org/bot';

    public function isEnabled(): bool
    {
        return filter_var(Setting::get('telegram_enabled'), FILTER_VALIDATE_BOOL);
    }

    public function sendNewReservationPending(Reservation $reservation): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $token = Setting::get('telegram_bot_token');
        $chatId = Setting::get('telegram_chat_id');
        if (empty($token) || empty($chatId)) {
            return false;
        }

        $reservation->load('event');
        $eventName = $reservation->event?->name ?? 'Evento';
        $url = route('admin.reservations.index', ['status' => 'PENDIENTE_PAGO']);
        $message = "🆕 Nueva reserva pendiente de revisión\n\n"
            . "Evento: {$eventName}\n"
            . "Código: {$reservation->payment_code}\n"
            . "Revisar: {$url}";

        return $this->sendMessage($token, $chatId, $message);
    }

    public function sendMessage(string $token, string $chatId, string $text): bool
    {
        $verify = filter_var(env('TELEGRAM_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN);
        $url = self::API_BASE . $token . '/sendMessage';
        $response = Http::timeout(10)->withOptions(['verify' => $verify])->post($url, [
            'chat_id' => $chatId,
            'text' => $text,
        ]);

        if (! $response->successful()) {
            Log::warning('Telegram notification failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }

        return true;
    }
}
