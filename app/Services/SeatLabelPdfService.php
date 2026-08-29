<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Seat;
use App\Models\Section;
use App\Models\Venue;
use App\Support\SeatLabelLayout;
use App\Support\SectionLayoutColors;
use Illuminate\Support\Collection;

class SeatLabelPdfService
{
    /**
     * Etiquetas de butacas numeradas y de sectores sin asiento (capacidad),
     * ocupadas y libres, ordenadas por fila y número (A1, A2, A3, B1…).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function labelsForEvent(Event $event, ?int $sectionId = null): Collection
    {
        if (! $event->venue_id) {
            return collect();
        }

        $venue = Venue::query()
            ->with(['seats.section', 'sections'])
            ->find($event->venue_id);
        if ($venue === null) {
            return collect();
        }

        $occupiedIds = $event->occupiedSeatIds()->flip();
        $sections = $venue->sections;

        $labels = collect();

        $seatsQuery = $venue->seats()->with('section')->orderBy('row')->orderBy('number');
        if ($sectionId === 0) {
            $seatsQuery->whereNull('section_id');
        } elseif ($sectionId !== null) {
            $seatsQuery->where('section_id', $sectionId);
        }

        /** @var Seat $seat */
        foreach ($seatsQuery->get() as $seat) {
            $section = $seat->section;
            $labels->push($this->seatLabel($seat, $section, $occupiedIds->has($seat->id)));
        }

        foreach ($sections as $section) {
            if ($section->has_seats || $section->capacity === null || $section->capacity < 1) {
                continue;
            }
            if ($sectionId === 0 || ($sectionId !== null && (int) $section->id !== $sectionId)) {
                continue;
            }
            $reserved = $event->reservedCountForSection($section->id);
            $color = SectionLayoutColors::tripletForSection($section)['fill'];
            for ($i = 1; $i <= $section->capacity; $i++) {
                $labels->push([
                    'key' => 'ga-'.$section->id.'-'.$i,
                    'seat_id' => null,
                    'label' => $section->name.' '.$i,
                    'row' => 0,
                    'row_letter' => '',
                    'number' => $i,
                    'section_id' => (int) $section->id,
                    'section_name' => $section->name,
                    'occupied' => $i <= $reserved,
                    'color' => $color,
                    'sort_order' => (int) $section->sort_order,
                ]);
            }
        }

        $numbered = $labels
            ->filter(fn (array $label): bool => $label['seat_id'] !== null)
            ->sortBy([
                ['row', 'asc'],
                ['number', 'asc'],
            ])
            ->values();

        $generalAdmission = $labels
            ->filter(fn (array $label): bool => $label['seat_id'] === null)
            ->sortBy([
                ['sort_order', 'asc'],
                ['number', 'asc'],
            ])
            ->values();

        return $numbered->concat($generalAdmission)->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function previewPayload(Event $event): array
    {
        $labels = $this->labelsForEvent($event);
        $venue = Venue::query()->with('sections')->find($event->venue_id);
        $sections = [];
        foreach ($venue?->sections ?? [] as $section) {
            $inSection = $labels->where('section_id', $section->id);
            $sections[] = [
                'id' => (int) $section->id,
                'name' => $section->name,
                'color' => SectionLayoutColors::tripletForSection($section)['fill'],
                'count' => $inSection->count(),
                'occupied' => $inSection->where('occupied', true)->count(),
            ];
        }

        $unsectioned = $labels->whereNull('section_id');
        if ($unsectioned->isNotEmpty()) {
            $sections[] = [
                'id' => 0,
                'name' => 'Sin sector',
                'color' => '#64748B',
                'count' => $unsectioned->count(),
                'occupied' => $unsectioned->where('occupied', true)->count(),
            ];
        }

        return [
            'event_name' => $event->name,
            'event_date' => $event->starts_at->translatedFormat('d/m/Y H:i'),
            'venue_name' => $this->venueName($event),
            'cover_url' => $event->cover_image_path ? asset('storage/'.$event->cover_image_path) : null,
            'labels' => $labels->map(fn (array $l) => [
                'key' => $l['key'],
                'label' => $l['label'],
                'section_id' => $l['section_id'],
                'section' => $l['section_name'],
                'occupied' => $l['occupied'],
                'color' => $l['color'],
            ])->values()->all(),
            'sections' => $sections,
            'total' => $labels->count(),
            'occupied' => $labels->where('occupied', true)->count(),
            'defaults' => [
                'per_page' => SeatLabelLayout::DEFAULT_PER_PAGE,
                'opacity' => SeatLabelLayout::DEFAULT_OPACITY,
                'per_page_options' => SeatLabelLayout::PER_PAGE_OPTIONS,
            ],
        ];
    }

    /**
     * @param  array{
     *     per_page: int,
     *     section_id: int|null,
     *     color_mode: string,
     *     custom_color: string|null,
     *     overlay_opacity: int,
     *     section_colors: array<int, string>,
     *     orientation: string
     * }  $options
     * @return array<string, mixed>
     */
    public function buildPdfData(Event $event, array $options): array
    {
        $perPage = SeatLabelLayout::normalizePerPage($options['per_page']);
        $opacity = SeatLabelLayout::normalizeOpacity($options['overlay_opacity']);
        $orientation = $options['orientation'] === 'landscape' ? 'landscape' : 'portrait';
        $grid = SeatLabelLayout::grid($perPage, $orientation);
        $fonts = SeatLabelLayout::fontSizes($perPage);
        $inner = SeatLabelLayout::pageInnerMm($orientation);

        $labels = $this->labelsForEvent($event, $options['section_id']);
        $customColor = SectionLayoutColors::normalize($options['custom_color'] ?? null);
        $sectionColors = [];
        foreach ($options['section_colors'] as $id => $hex) {
            $n = SectionLayoutColors::normalize($hex);
            if ($n !== null) {
                $sectionColors[(int) $id] = $n;
            }
        }

        $prepared = $labels->map(function (array $label) use ($options, $customColor, $sectionColors, $opacity) {
            $overlay = null;
            if ($options['color_mode'] === 'custom') {
                $overlay = $customColor;
            } else {
                $sid = $label['section_id'];
                if ($sid !== null && isset($sectionColors[$sid])) {
                    $overlay = $sectionColors[$sid];
                } else {
                    $overlay = $label['color'];
                }
            }

            $label['overlay_color'] = $overlay;
            $label['overlay_rgba'] = SeatLabelLayout::rgba($overlay, $opacity);
            $label['overlay_tint'] = SeatLabelLayout::tintedHex($overlay, $opacity);

            return $label;
        });

        $pages = $prepared->chunk($perPage)->values();
        $rowHeightMm = $grid['rows'] > 0 ? round($inner['height'] / $grid['rows'], 2) : $inner['height'];
        $colWidthPct = $grid['cols'] > 0 ? round(100 / $grid['cols'], 4) : 100;

        return [
            'event' => $event,
            'eventName' => $event->name,
            'eventDate' => $event->starts_at->translatedFormat('l d/m/Y H:i'),
            'venueName' => $this->venueName($event),
            'coverPath' => $this->coverFilePath($event),
            'pages' => $pages,
            'cols' => $grid['cols'],
            'rows' => $grid['rows'],
            'perPage' => $perPage,
            'pageCount' => SeatLabelLayout::pageCount($prepared->count(), $perPage),
            'fonts' => $fonts,
            'rowHeightMm' => $rowHeightMm,
            'colWidthPct' => $colWidthPct,
            'inner' => $inner,
            'orientation' => $orientation,
            'opacity' => $opacity,
        ];
    }

    public function coverFilePath(Event $event): ?string
    {
        if (! $event->cover_image_path) {
            return null;
        }
        $path = storage_path('app/public/'.$event->cover_image_path);

        return is_file($path) ? str_replace('\\', '/', $path) : null;
    }

    private function venueName(Event $event): string
    {
        if ($event->venue_id) {
            $name = Venue::query()->whereKey($event->venue_id)->value('name');
            if (is_string($name) && $name !== '') {
                return $name;
            }
        }

        return (string) ($event->getAttributes()['venue'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    private function seatLabel(Seat $seat, ?Section $section, bool $occupied): array
    {
        $color = $section ? SectionLayoutColors::tripletForSection($section)['fill'] : null;

        return [
            'key' => 'seat-'.$seat->id,
            'seat_id' => (int) $seat->id,
            'label' => $seat->row_letter.$seat->number,
            'row' => (int) $seat->row,
            'row_letter' => $seat->row_letter,
            'number' => (int) $seat->number,
            'section_id' => $seat->section_id ? (int) $seat->section_id : null,
            'section_name' => $section?->name,
            'occupied' => $occupied,
            'color' => $color,
            'sort_order' => (int) ($section?->sort_order ?? 9999),
        ];
    }
}
