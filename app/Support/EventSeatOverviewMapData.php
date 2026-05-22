<?php

namespace App\Support;

use App\Models\Event;
use Illuminate\Support\Collection;

class EventSeatOverviewMapData
{
    /**
     * Seat overview map for an event (WYSIWYG layout + optional grid fallback data).
     *
     * @return array<string, mixed>|null null when the event has no venue
     */
    public static function forEvent(Event $event, bool $withAdminBlockUrls = false): ?array
    {
        if (! $event->venue_id) {
            return null;
        }

        $event->load('venue.seats', 'venue.layoutElements.seat');
        $venue = $event->getRelationValue('venue');
        if (! $venue) {
            return null;
        }

        $seats = $venue->seats()->orderBy('row')->orderBy('number')->get();
        $seatsByRow = $seats->groupBy('row');
        $occupiedSeatIds = $event->occupiedSeatIds()->flip();
        $blockedSeatIds = $event->blockedSeatIds()->flip();
        $layoutElements = $venue->layoutElements->map(function ($element) use ($occupiedSeatIds, $blockedSeatIds, $event, $withAdminBlockUrls) {
            $seat = $element->seat;
            $isOccupied = false;
            $isBlockedForEvent = false;
            $isBlockedGlobally = false;
            if ($seat) {
                $isOccupied = $occupiedSeatIds->has($seat->id);
                $isBlockedForEvent = $blockedSeatIds->has($seat->id);
                $isBlockedGlobally = (bool) $seat->blocked;
            }

            $seatPayload = $seat ? [
                'id' => $seat->id,
                'label' => $seat->display_label,
                'row' => $seat->row,
                'number' => $seat->number,
                'section_id' => $seat->section_id,
                'occupied' => $isOccupied,
                'blocked_globally' => $isBlockedGlobally,
                'blocked_for_event' => $isBlockedForEvent,
            ] : null;

            if ($seatPayload && $withAdminBlockUrls) {
                $seatPayload['block_url'] = route('admin.events.seats.block', [$event, $seat]);
                $seatPayload['unblock_url'] = route('admin.events.seats.unblock', [$event, $seat]);
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
                'seat' => $seatPayload,
            ];
        })->values();

        $venue->load('sections');
        $sectionPaletteById = [];
        $sectionPalettesById = [];
        foreach ($venue->sections as $sec) {
            $t = SectionLayoutColors::tripletForSection($sec);
            $sectionPaletteById[$sec->id] = ['bg' => $t['fill'], 'border' => $t['stroke'], 'text' => $t['text']];
            $sectionPalettesById[$sec->id] = ['fill' => $t['fill'], 'stroke' => $t['stroke'], 'text' => $t['text']];
        }
        $legendSampleSeatStyle = $venue->sections->isNotEmpty()
            ? $sectionPaletteById[$venue->sections->first()->id]
            : ['bg' => '#2563eb', 'border' => '#1e40af', 'text' => '#ffffff'];
        $layoutCanvas = [
            'width' => $venue->layout_canvas_width,
            'height' => $venue->layout_canvas_height,
        ];
        $layoutElementsData = $layoutElements->values()->all();
        $hasCustomLayout = $layoutElements->isNotEmpty();

        return compact(
            'seatsByRow',
            'occupiedSeatIds',
            'blockedSeatIds',
            'layoutElements',
            'layoutElementsData',
            'hasCustomLayout',
            'sectionPaletteById',
            'sectionPalettesById',
            'legendSampleSeatStyle',
            'layoutCanvas',
        );
    }

    /**
     * @param  Collection<int, \Illuminate\Support\Collection<int, \App\Models\Seat>>  $seatsByRow
     */
    public static function maxColsForGrid(Collection $seatsByRow): int
    {
        return $seatsByRow->isEmpty() ? 1 : (int) $seatsByRow->max(fn ($r) => $r->count());
    }
}
