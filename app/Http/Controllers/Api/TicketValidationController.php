<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\ReservationTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Valida el código QR del ticket (formato: payment_code-position) y marca el ticket como usado.
 *
 * Request: POST /api/v1/tickets/validate
 * Body: { "code": "EV-ABC123-1" }  or  Query: ?code=EV-ABC123-1
 * Header: X-API-Key: <TICKET_VALIDATOR_API_KEY>
 *
 * Responses:
 * - 200 valid=true:  { "valid": true, "already_used": false, "holder_name": "...", "seat": "A-12"|"Parado" }
 * - 200 already used (advertencia amarillo): { "valid": false, "already_used": true, "display_type": "warning", "message": "YA PASO", "holder_name": "...", "seat": "...", "payment_code": "...", "event_name": "...", "validated_at": "..." }
 * - 404: { "valid": false, "message": "Código no válido" }
 * - 400/403: { "valid": false, "message": "Reserva no confirmada" }
 *
 * Test mode (TICKET_VALIDATOR_TEST_FORCE_VALID): true=valid, false=invalid, warning=already_used
 */
class TicketValidationController extends Controller
{
    public function validateTicket(Request $request): JsonResponse
    {
        $forced = $this->forcedTestValidation();
        if ($forced !== null) {
            return response()->json($forced, 200);
        }

        $code = $request->input('code') ?? $request->query('code');
        if (empty($code) || ! is_string($code)) {
            return response()->json([
                'valid' => false,
                'message' => 'Código no válido',
            ], 404);
        }

        $parsed = $this->parseCode($code);
        if ($parsed === null) {
            return response()->json([
                'valid' => false,
                'message' => 'Código no válido',
            ], 404);
        }

        [$paymentCode, $position] = $parsed;

        $reservation = Reservation::where('payment_code', $paymentCode)->first();
        if (! $reservation) {
            return response()->json([
                'valid' => false,
                'message' => 'Código no válido',
            ], 404);
        }

        if ($reservation->status !== Reservation::STATUS_CONFIRMADO) {
            return response()->json([
                'valid' => false,
                'message' => 'Reserva no confirmada',
            ], 403);
        }

        $ticket = ReservationTicket::where('reservation_id', $reservation->id)
            ->where('position', $position)
            ->with(['seat', 'section'])
            ->first();

        if (! $ticket) {
            return response()->json([
                'valid' => false,
                'message' => 'Código no válido',
            ], 404);
        }

        $holderName = $ticket->holder_name;
        $seatLabel = $this->seatLabel($ticket);

        if ($ticket->validated_at !== null) {
            return response()->json(
                $this->alreadyUsedResponse($reservation, $ticket, $holderName, $seatLabel),
                200
            );
        }

        // Atomic update: evita race condition donde dos validaciones simultáneas
        // podrían marcar el mismo ticket como válido. Solo una actualización puede
        // afectar la fila cuando validated_at es NULL.
        $updated = ReservationTicket::where('id', $ticket->id)
            ->whereNull('validated_at')
            ->update(['validated_at' => now()]);

        if ($updated === 0) {
            $ticket->refresh();
            return response()->json(
                $this->alreadyUsedResponse($reservation, $ticket, $holderName, $seatLabel),
                200
            );
        }

        return response()->json([
            'valid' => true,
            'already_used' => false,
            'holder_name' => $holderName,
            'seat' => $seatLabel,
        ], 200);
    }

    private function forcedTestValidation(): ?array
    {
        if (! config('services.ticket_validator.test_mode')) {
            return null;
        }

        $raw = config('services.ticket_validator.test_force_valid');
        if ($raw === null || $raw === '') {
            return null;
        }

        $rawNormalized = is_string($raw) ? strtoupper(trim($raw)) : $raw;

        if ($rawNormalized === 'WARNING') {
            return [
                'valid' => false,
                'already_used' => true,
                'display_type' => 'warning',
                'message' => 'YA PASO',
                'holder_name' => 'TEST MODE',
                'seat' => 'Parado',
                'payment_code' => 'TEST-CODE',
                'event_name' => 'Evento Test',
            ];
        }

        $isValid = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($isValid === null) {
            return null;
        }

        if ($isValid) {
            return [
                'valid' => true,
                'already_used' => false,
                'holder_name' => 'TEST MODE',
                'seat' => 'Parado',
                'message' => 'Validación forzada por modo test',
            ];
        }

        return [
            'valid' => false,
            'already_used' => false,
            'message' => 'Validación rechazada por modo test',
        ];
    }

    /**
     * @return array{0: string, 1: int}|null [payment_code, position]
     */
    private function parseCode(string $code): ?array
    {
        $parts = explode('-', $code);
        if (count($parts) < 2) {
            return null;
        }
        $positionStr = end($parts);
        if (! ctype_digit($positionStr)) {
            return null;
        }
        $position = (int) $positionStr;
        array_pop($parts);
        $paymentCode = implode('-', $parts);
        return $paymentCode !== '' ? [$paymentCode, $position] : null;
    }

    /**
     * Respuesta para ticket ya usado: advertencia amarilla "YA PASO" con datos de reserva y asiento.
     */
    private function alreadyUsedResponse(
        Reservation $reservation,
        ReservationTicket $ticket,
        string $holderName,
        string $seatLabel
    ): array {
        $reservation->loadMissing('event');

        $payload = [
            'valid' => false,
            'already_used' => true,
            'display_type' => 'warning',
            'message' => 'YA PASO',
            'holder_name' => $holderName,
            'seat' => $seatLabel,
            'payment_code' => $reservation->payment_code,
            'event_name' => $reservation->event?->name,
        ];

        if ($ticket->validated_at !== null) {
            $payload['validated_at'] = $ticket->validated_at->format('Y-m-d H:i:s');
        }

        return $payload;
    }

    private function seatLabel(ReservationTicket $ticket): string
    {
        if ($ticket->seat) {
            return $ticket->seat->display_label;
        }
        if ($ticket->section) {
            return $ticket->section->name;
        }
        return 'Parado';
    }
}
