<?php

namespace App\Support;

use App\Models\Event;

class EventSeatSelectionData
{
    /**
     * @return array<string, mixed>
     */
    public static function build(Event $event): array
    {
        $event->load('sections.seats', 'venue.seats', 'venue.layoutElements.seat');

        $seats = [];
        $seatsMap = [];
        $availableSeatIds = [];
        $sectionsData = [];
        $layoutElements = [];
        $seatIdToPrice = [];
        $sectionIdToPrice = [];
        $sectionIdToName = [];
        $layoutCanvas = ['width' => null, 'height' => null];
        $sectionPalettesById = [];

        if ($event->venue_id) {
            $venue = $event->getRelationValue('venue');
            if ($venue) {
                $venue->loadMissing('sections');
                foreach ($venue->sections as $sec) {
                    $t = SectionLayoutColors::tripletForSection($sec);
                    $sectionPalettesById[$sec->id] = ['fill' => $t['fill'], 'stroke' => $t['stroke'], 'text' => $t['text']];
                }
                $layoutCanvas = [
                    'width' => $venue->layout_canvas_width,
                    'height' => $venue->layout_canvas_height,
                ];
                $blockedSeatIds = $event->blockedSeatIds()->flip();
                $layoutElements = $venue->layoutElements->map(function ($element) use ($blockedSeatIds) {
                    $seat = $element->seat;
                    $isBlocked = false;
                    if ($seat) {
                        $isBlocked = (bool) $seat->blocked || $blockedSeatIds->has($seat->id);
                    }

                    return [
                        'id' => $element->id,
                        'type' => $element->type,
                        'seat_id' => $element->seat_id,
                        'x' => (float) $element->x,
                        'y' => (float) $element->y,
                        'w' => (float) $element->w,
                        'h' => (float) $element->h,
                        'rotation' => (float) $element->rotation,
                        'z_index' => (int) $element->z_index,
                        'meta' => $element->meta ?? [],
                        'seat' => $seat ? [
                            'id' => $seat->id,
                            'label' => $seat->display_label,
                            'row' => $seat->row,
                            'number' => $seat->number,
                            'section_id' => $seat->section_id,
                            'blocked' => $isBlocked,
                        ] : null,
                    ];
                })->values()->all();

                if ($event->hasSections()) {
                    foreach ($event->sections as $section) {
                        $pivot = $section->pivot;
                        $listPrice = $pivot->price !== null ? (float) $pivot->price : null;
                        $price = $listPrice !== null ? $event->applyPresaleDiscount($listPrice, $section) : null;
                        if ($section->has_seats) {
                            $sectionSeats = $section->seats()->orderBy('row')->orderBy('number')->get()->map(function ($seat) use ($blockedSeatIds) {
                                $seat->blocked = (bool) $seat->blocked || $blockedSeatIds->has($seat->id);

                                return $seat;
                            });
                            if ($sectionSeats->isEmpty() && $section->row_start !== null && $section->row_end !== null) {
                                $fallbackSeats = $venue->seats();
                                $section->applySeatSpatialConstraints($fallbackSeats);
                                $sectionSeats = $fallbackSeats->orderBy('row')->orderBy('number')->get()->map(function ($seat) use ($blockedSeatIds) {
                                    $seat->blocked = (bool) $seat->blocked || $blockedSeatIds->has($seat->id);

                                    return $seat;
                                });
                            }
                            if ($sectionSeats->isEmpty()) {
                                $sectionSeats = $venue->seats()->orderBy('row')->orderBy('number')->get()->map(function ($seat) use ($blockedSeatIds) {
                                    $seat->blocked = (bool) $seat->blocked || $blockedSeatIds->has($seat->id);

                                    return $seat;
                                });
                            }
                            $sectionAvailableIds = $event->availableSeats($section->id)->pluck('id')->all();
                            if (empty($sectionAvailableIds) && $section->row_start !== null && $section->row_end !== null) {
                                $avail = $event->availableSeats(null);
                                $section->applySeatSpatialConstraints($avail);
                                $sectionAvailableIds = $avail->pluck('id')->all();
                            }
                            if (empty($sectionAvailableIds)) {
                                $sectionAvailableIds = $event->availableSeats(null)->pluck('id')->all();
                            }
                            $sectionsData[] = [
                                'id' => $section->id,
                                'name' => $section->name,
                                'price' => $price,
                                'list_price' => $listPrice,
                                'has_seats' => true,
                                'seats' => $sectionSeats,
                                'availableSeatIds' => array_values($sectionAvailableIds),
                                'palette' => SectionLayoutColors::tripletForSection($section),
                            ];
                        } else {
                            $availableCapacity = $event->availableCapacityForSection($section);
                            $sectionsData[] = [
                                'id' => $section->id,
                                'name' => $section->name,
                                'price' => $price,
                                'list_price' => $listPrice,
                                'has_seats' => false,
                                'capacity' => $section->capacity,
                                'availableCapacity' => $availableCapacity,
                                'palette' => SectionLayoutColors::tripletForSection($section),
                            ];
                        }
                    }
                    $seatsMap = [];
                    $seatIdToPrice = [];
                    $sectionIdToPrice = [];
                    $sectionIdToName = [];
                    foreach ($sectionsData as $sd) {
                        $sectionIdToPrice[$sd['id']] = $sd['price'] ?? null;
                        $sectionIdToName[$sd['id']] = $sd['name'];
                        if (! empty($sd['seats'])) {
                            foreach ($sd['seats'] as $seat) {
                                $seatsMap[$seat->id] = $seat->display_label;
                                $seatIdToPrice[$seat->id] = $sd['price'] ?? null;
                            }
                        }
                    }
                } else {
                    $seats = $venue->seats()->orderBy('row')->orderBy('number')->get()->map(function ($seat) use ($blockedSeatIds) {
                        $seat->blocked = (bool) $seat->blocked || $blockedSeatIds->has($seat->id);

                        return $seat;
                    });
                    $seatsMap = $seats->keyBy('id')->map(fn ($s) => ['label' => $s->display_label])->all();
                    $availableSeatIds = $event->availableSeats()->pluck('id')->all();
                }
            }
        }

        return compact(
            'seats',
            'seatsMap',
            'availableSeatIds',
            'sectionsData',
            'layoutElements',
            'seatIdToPrice',
            'sectionIdToPrice',
            'sectionIdToName',
            'layoutCanvas',
            'sectionPalettesById'
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function withZeroPrices(array $data): array
    {
        foreach ($data['sectionsData'] as $key => $section) {
            $section['price'] = 0;
            $section['list_price'] = 0;
            $data['sectionsData'][$key] = $section;
        }

        foreach (array_keys($data['seatIdToPrice'] ?? []) as $seatId) {
            $data['seatIdToPrice'][$seatId] = 0;
        }

        foreach (array_keys($data['sectionIdToPrice'] ?? []) as $sectionId) {
            $data['sectionIdToPrice'][$sectionId] = 0;
        }

        return $data;
    }
}
