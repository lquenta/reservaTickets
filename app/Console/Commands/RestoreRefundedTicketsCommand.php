<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Services\RefundService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class RestoreRefundedTicketsCommand extends Command
{
    protected $signature = 'refunds:restore
                            {payment_code : Código de pago de la reserva}
                            {ticket_ids?* : IDs de reservation_tickets (espacio o coma)}
                            {--list : Solo listar entradas de la reserva}
                            {--dry-run : Simular sin guardar cambios}';

    protected $description = 'Revierte reembolsos erróneos; los QR (payment_code-position) no cambian.';

    public function handle(RefundService $refundService): int
    {
        $paymentCode = $this->argument('payment_code');

        $reservation = Reservation::query()
            ->where('payment_code', $paymentCode)
            ->with(['reservationTickets.seat', 'reservationTickets.section', 'event'])
            ->first();

        if (! $reservation) {
            $this->error("No existe reserva con código: {$paymentCode}");

            return self::FAILURE;
        }

        if ($this->option('list') || $this->argument('ticket_ids') === []) {
            $this->info("Reserva #{$reservation->id} · {$reservation->status} · {$reservation->sale_type}");
            $rows = $reservation->reservationTickets->map(fn ($t) => [
                $t->id,
                $t->position,
                $t->holder_name,
                $t->seat?->display_label ?? '—',
                $t->refunded_at ? 'sí' : 'no',
                $t->validated_at ? 'sí' : 'no',
                $reservation->payment_code.'-'.$t->position,
            ])->all();

            $this->table(
                ['id', 'pos', 'nombre', 'butaca', 'reemb.', 'validada', 'código QR'],
                $rows
            );

            if ($this->option('list') || $this->argument('ticket_ids') === []) {
                $this->line('Uso: php artisan refunds:restore '.$paymentCode.' 12 13');
            }

            return self::SUCCESS;
        }

        $ticketIds = $this->parseTicketIds($this->argument('ticket_ids'));

        try {
            if ($this->option('dry-run')) {
                $refundService->restoreRefundedTickets($reservation, $ticketIds, dryRun: true);
                $this->warn('Dry-run: no se guardó nada. Las butacas están libres para restaurar.');

                return self::SUCCESS;
            }

            $refundService->restoreRefundedTickets($reservation, $ticketIds);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Entradas restauradas en {$paymentCode}. Los QR enviados siguen siendo válidos.");

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $arguments
     * @return list<int>
     */
    private function parseTicketIds(array $arguments): array
    {
        $ids = [];
        foreach ($arguments as $arg) {
            foreach (preg_split('/[\s,;]+/', (string) $arg, -1, PREG_SPLIT_NO_EMPTY) as $part) {
                if (ctype_digit($part)) {
                    $ids[] = (int) $part;
                }
            }
        }

        return array_values(array_unique($ids));
    }
}
