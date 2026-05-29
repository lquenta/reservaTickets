<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Models\ReservationTicket;
use App\Services\ReservationTicketAssignmentService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class AssignReservationSeatsCommand extends Command
{
    protected $signature = 'tickets:assign
                            {payment_code : Código de pago de la reserva}
                            {pairs?* : Cambios id|pN:butaca:nombre — ej. 12:B2:Juan 13:A3: o p1::María}
                            {--list : Listar entradas actuales}
                            {--dry-run : Simular sin guardar}
                            {--force : Permitir aunque la entrada ya fue validada en puerta}';

    protected $description = 'Cambia titular y/o butaca de entradas; el QR (código-posición) no cambia.';

    public function handle(ReservationTicketAssignmentService $assignmentService): int
    {
        $paymentCode = $this->argument('payment_code');

        $reservation = Reservation::query()
            ->where('payment_code', $paymentCode)
            ->with(['reservationTickets.seat', 'event'])
            ->first();

        if (! $reservation) {
            $this->error("No existe reserva con código: {$paymentCode}");

            return self::FAILURE;
        }

        if ($this->option('list') || $this->argument('pairs') === []) {
            $this->printTicketsTable($reservation);
            $this->line('');
            $this->line('Ejemplos:');
            $this->line("  php artisan tickets:assign {$paymentCode} 12:B2:Juan Pérez");
            $this->line("  php artisan tickets:assign {$paymentCode} 12:B2:        (solo butaca)");
            $this->line("  php artisan tickets:assign {$paymentCode} 12::Ana López  (solo nombre)");
            $this->line("  php artisan tickets:assign {$paymentCode} p1:A3:Pedro     (por posición 1)");

            return self::SUCCESS;
        }

        if (! in_array($reservation->status, [Reservation::STATUS_CONFIRMADO, Reservation::STATUS_PENDIENTE_PAGO], true)) {
            $this->error('Solo se pueden editar reservas CONFIRMADO o PENDIENTE_PAGO.');

            return self::FAILURE;
        }

        try {
            $updates = $this->parsePairs($reservation, $this->argument('pairs'));
            $assignmentService->apply($reservation, $updates, (bool) $this->option('dry-run'));
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry-run: no se guardó nada.');
        } else {
            $this->info("Entradas actualizadas en {$paymentCode}. Los QR enviados siguen siendo los mismos.");
        }

        $this->printTicketsTable($reservation->fresh(['reservationTickets.seat']));

        return self::SUCCESS;
    }

    private function printTicketsTable(Reservation $reservation): void
    {
        $this->info("Reserva #{$reservation->id} · {$reservation->status} · {$reservation->sale_type}");
        $rows = $reservation->reservationTickets->sortBy('position')->map(fn (ReservationTicket $t) => [
            $t->id,
            $t->position,
            $t->holder_name,
            $t->seat?->display_label ?? '—',
            $t->refunded_at ? 'sí' : 'no',
            $t->validated_at ? 'sí' : 'no',
            $reservation->payment_code.'-'.$t->position,
        ])->values()->all();

        $this->table(
            ['id', 'pos', 'nombre', 'butaca', 'reemb.', 'validada', 'código QR'],
            $rows
        );
    }

    /**
     * @param  array<int, string>  $pairArguments
     * @return list<array{ticket: ReservationTicket, seat_label: ?string, holder_name: ?string}>
     */
    private function parsePairs(Reservation $reservation, array $pairArguments): array
    {
        $updates = [];
        $rawPairs = [];
        foreach ($pairArguments as $arg) {
            $arg = trim((string) $arg);
            if ($arg === '') {
                continue;
            }
            $chunks = preg_split('/\s+(?=(?:p)?\d+:)/i', $arg, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($chunks ?: [$arg] as $chunk) {
                $rawPairs[] = trim($chunk);
            }
        }

        foreach ($rawPairs as $raw) {
            $parts = explode(':', $raw, 3);
            if (count($parts) < 2) {
                throw new InvalidArgumentException("Formato inválido «{$raw}». Usa id:butaca:nombre (ej. 12:B2:Juan).");
            }

            $identifier = trim($parts[0]);
            $seatLabel = isset($parts[1]) ? trim($parts[1]) : null;
            $holderName = isset($parts[2]) ? trim($parts[2]) : null;

            if ($seatLabel === '') {
                $seatLabel = null;
            }
            if ($holderName === '') {
                $holderName = null;
            }

            if ($seatLabel === null && $holderName === null) {
                throw new InvalidArgumentException("Sin cambios en «{$raw}».");
            }

            $ticket = $this->resolveTicket($reservation, $identifier);

            if ($ticket->validated_at !== null && ! $this->option('force')) {
                throw new InvalidArgumentException(
                    "La entrada #{$ticket->id} ya fue validada en puerta. Usa --force si debes cambiarla igual."
                );
            }

            $updates[] = [
                'ticket' => $ticket,
                'seat_label' => $seatLabel,
                'holder_name' => $holderName,
            ];
        }

        return $updates;
    }

    private function resolveTicket(Reservation $reservation, string $identifier): ReservationTicket
    {
        if (preg_match('/^p(\d+)$/i', $identifier, $m)) {
            $ticket = $reservation->reservationTickets->firstWhere('position', (int) $m[1]);
            if ($ticket) {
                return $ticket;
            }
            throw new InvalidArgumentException("No hay entrada con posición {$m[1]} en esta reserva.");
        }

        if (! ctype_digit($identifier)) {
            throw new InvalidArgumentException("Identificador inválido: {$identifier}. Usa el id de la tabla o p1, p2…");
        }

        $ticket = $reservation->reservationTickets->firstWhere('id', (int) $identifier);
        if ($ticket) {
            return $ticket;
        }

        throw new InvalidArgumentException("No hay entrada #{$identifier} en esta reserva.");
    }
}
