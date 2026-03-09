<?php

namespace App\Http\Requests;

use App\Models\Event;
use App\Services\RecaptchaService;
use App\Services\ReservationAuditService;
use App\Services\ReservationService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator as ValidationValidator;
use Illuminate\Validation\ValidationException;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $seatIds = $this->input('seat_ids');
        if (! is_array($seatIds) || empty($seatIds)) {
            $csv = $this->input('seat_ids_csv');
            if (is_string($csv) && $csv !== '') {
                $this->merge(['seat_ids' => array_values(array_filter(array_map('intval', explode(',', $csv))))]);
            }
        }
    }

    public function rules(): array
    {
        $event = Event::find($this->input('event_id'));
        $rules = [
            'event_id' => ['required', 'exists:events,id'],
            'single_name' => ['nullable', 'boolean'],
            'g-recaptcha-response' => [config('services.recaptcha.secret_key') ? 'required' : 'nullable', 'string'],
        ];

        if ($event && $event->venue_id) {
            if ($event->hasSections()) {
                $rules['seat_ids'] = ['nullable', 'array', 'max:' . ReservationService::MAX_SEATS];
                $rules['seat_ids.*'] = ['integer', 'exists:seats,id'];
                $rules['section_quantities'] = ['nullable', 'array'];
                $rules['section_quantities.*'] = ['nullable', 'integer', 'min:0'];
                $seatCount = is_array($this->input('seat_ids')) ? count(array_filter($this->input('seat_ids'))) : 0;
                $sectionQtys = is_array($this->input('section_quantities')) ? array_map('intval', $this->input('section_quantities')) : [];
                $totalTickets = $seatCount + array_sum($sectionQtys);
                if ($totalTickets > 0) {
                    if ($this->boolean('single_name')) {
                        $rules['holder_name'] = ['required', 'string', 'max:255', 'regex:/^[\pL\pM\s\-\.\'\x{2019}\x{2018},]+$/u'];
                    } else {
                        for ($i = 1; $i <= min($totalTickets, ReservationService::MAX_SEATS); $i++) {
                            $rules["holder_name_{$i}"] = ['required', 'string', 'max:255', 'regex:/^[\pL\pM\s\-\.\'\x{2019}\x{2018},]+$/u'];
                        }
                    }
                }
            } else {
                $rules['seat_ids'] = ['required', 'array', 'min:1', 'max:' . ReservationService::MAX_SEATS];
                $rules['seat_ids.*'] = ['required', 'integer', 'exists:seats,id'];
                $count = is_array($this->input('seat_ids')) ? count($this->input('seat_ids')) : 0;
                if ($count > 0) {
                    if ($this->boolean('single_name')) {
                        $rules['holder_name'] = ['required', 'string', 'max:255', 'regex:/^[\pL\pM\s\-\.\'\x{2019}\x{2018},]+$/u'];
                    } else {
                        $seatIds = array_map('intval', (array) $this->input('seat_ids'));
                        for ($i = 1; $i <= $count; $i++) {
                            $rules["holder_name_{$i}"] = ['required', 'string', 'max:255', 'regex:/^[\pL\pM\s\-\.\'\x{2019}\x{2018},]+$/u'];
                            $rules["seat_for_{$i}"] = ['required', 'integer', 'in:'.implode(',', $seatIds)];
                        }
                    }
                }
            }
        } else {
            $quantity = (int) $this->input('quantity', 1);
            $rules['quantity'] = ['required', 'integer', 'min:1', 'max:4'];
            if ($this->boolean('single_name')) {
                $rules['holder_name'] = ['required', 'string', 'max:255', 'regex:/^[\pL\pM\s\-\.\'\x{2019}\x{2018},]+$/u'];
            } else {
                for ($i = 1; $i <= $quantity; $i++) {
                    $rules["holder_name_{$i}"] = ['required', 'string', 'max:255', 'regex:/^[\pL\pM\s\-\.\'\x{2019}\x{2018},]+$/u'];
                }
            }
        }

        return $rules;
    }

    public function withValidator(ValidationValidator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $event = Event::with('venue', 'sections')->find($this->input('event_id'));
            if (! $event || ! $event->venue_id) {
                return;
            }
            $seatIds = is_array($this->input('seat_ids')) ? array_map('intval', array_filter($this->input('seat_ids'))) : [];
            $sectionQuantities = is_array($this->input('section_quantities')) ? array_filter(array_map('intval', $this->input('section_quantities'))) : [];
            $totalTickets = count($seatIds) + array_sum($sectionQuantities);

            if (! empty($seatIds) && count($seatIds) !== count(array_unique($seatIds))) {
                $validator->errors()->add('seat_ids', 'Cada butaca solo puede asignarse a una persona. No elijas la misma butaca más de una vez.');
                return;
            }

            if ($event->hasSections()) {
                if ($totalTickets < 1 || $totalTickets > ReservationService::MAX_SEATS) {
                    $validator->errors()->add('seat_ids', 'El total de entradas debe ser entre 1 y ' . ReservationService::MAX_SEATS . '.');
                }
                if (! empty($seatIds)) {
                    $venue = $event->getRelationValue('venue');
                    if ($venue) {
                        $venueSeatIds = $venue->seats()->pluck('id')->flip();
                        foreach ($seatIds as $id) {
                            if (! $venueSeatIds->has($id)) {
                                $validator->errors()->add('seat_ids', 'Todas las butacas deben pertenecer al lugar de este evento.');
                                return;
                            }
                        }
                    }
                    $availableIds = $event->sections->where('has_seats', true)->flatMap(function ($s) use ($event) {
                        $ids = $event->availableSeats($s->id)->pluck('id');
                        if ($ids->isEmpty() && $s->row_start !== null && $s->row_end !== null) {
                            $ids = $event->availableSeats(null)->whereBetween('row', [$s->row_start, $s->row_end])->pluck('id');
                        }
                        if ($ids->isEmpty()) {
                            $ids = $event->availableSeats(null)->pluck('id');
                        }
                        return $ids;
                    })->flip();
                    foreach ($seatIds as $id) {
                        if (! $availableIds->has($id)) {
                            $validator->errors()->add('seat_ids', 'Una o más butacas ya no están disponibles.');
                            return;
                        }
                    }
                }
                return;
            }

            if (empty($seatIds)) {
                return;
            }
            $venue = $event->getRelationValue('venue');
            if (! $venue) {
                return;
            }
            $venueSeatIds = $venue->seats()->pluck('id')->flip();
            foreach ($seatIds as $id) {
                if (! $venueSeatIds->has($id)) {
                    $validator->errors()->add('seat_ids', 'Todas las butacas deben pertenecer al lugar de este evento.');
                    return;
                }
            }
            $availableIds = $event->availableSeats()->pluck('id')->flip();
            foreach ($seatIds as $id) {
                if (! $availableIds->has($id)) {
                    $validator->errors()->add('seat_ids', 'Una o más butacas ya no están disponibles. Elige otras.');
                    return;
                }
            }
            if (! $this->boolean('single_name') && count($seatIds) > 0) {
                $assignments = [];
                for ($i = 1; $i <= count($seatIds); $i++) {
                    $assignments[] = (int) $this->input("seat_for_{$i}");
                }
                sort($seatIds);
                $sortedAssignments = $assignments;
                sort($sortedAssignments);
                if ($sortedAssignments !== $seatIds) {
                    $validator->errors()->add('seat_for_1', 'Cada butaca debe estar asignada exactamente a un ticket. Revisa la asignación.');
                }
            }
        });
    }

    protected function passedValidation(): void
    {
        if (! config('services.recaptcha.secret_key')) {
            return;
        }
        $recaptcha = app(RecaptchaService::class);
        if (! $recaptcha->verify($this->input('g-recaptcha-response', ''))) {
            $event = Event::find($this->input('event_id'));
            app(ReservationAuditService::class)->log(
                \App\Models\ReservationAuditLog::ACTION_RESERVATION_ATTEMPT,
                \App\Models\ReservationAuditLog::RESULT_FAILED,
                auth()->user(),
                $event,
                null,
                'Verificación reCAPTCHA fallida'
            );
            throw ValidationException::withMessages([
                'g-recaptcha-response' => ['La verificación de seguridad ha fallado. Intente de nuevo.'],
            ]);
        }
    }

    protected function failedValidation(Validator $validator): void
    {
        $eventId = $this->input('event_id');
        $event = is_numeric($eventId) ? Event::find($eventId) : null;
        $message = $validator->errors()->first();
        app(ReservationAuditService::class)->log(
            \App\Models\ReservationAuditLog::ACTION_RESERVATION_ATTEMPT,
            \App\Models\ReservationAuditLog::RESULT_FAILED,
            auth()->user(),
            $event,
            null,
            $message
        );
        parent::failedValidation($validator);
    }

    public function messages(): array
    {
        $messages = [
            'holder_name.regex' => 'El nombre solo puede contener letras, espacios, guiones, comas y apóstrofos.',
            'g-recaptcha-response.required' => 'Debe completar la verificación de seguridad.',
        ];
        $count = is_array($this->input('seat_ids')) ? count($this->input('seat_ids')) : (int) $this->input('quantity', 1);
        for ($i = 1; $i <= max($count, 4); $i++) {
            $messages["holder_name_{$i}.regex"] = 'El nombre del ticket '.$i.' solo puede contener letras, espacios, guiones, comas y apóstrofos.';
        }
        return $messages;
    }
}
