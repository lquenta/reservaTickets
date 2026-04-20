<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Venue;
use App\Models\VenueLayoutElement;
use App\Support\SectionLayoutColors;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VenueController extends Controller
{
    public function index(): View
    {
        $venues = Venue::withCount('seats')->orderBy('name')->paginate(15);

        return view('admin.venues.index', compact('venues'));
    }

    public function create(): View
    {
        return view('admin.venues.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:venues,slug'],
            'address' => ['nullable', 'string', 'max:500'],
            'seat_rows' => ['required', 'integer', 'min:1', 'max:50'],
            'seat_columns' => ['required', 'integer', 'min:1', 'max:50'],
            'plan_image' => ['nullable', 'image', 'max:4096'],
        ]);

        $venue = Venue::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? null,
            'address' => $validated['address'] ?? null,
            'seat_rows' => (int) $validated['seat_rows'],
            'seat_columns' => (int) $validated['seat_columns'],
        ]);

        if ($request->hasFile('plan_image')) {
            $path = $request->file('plan_image')->store('venue-plans', 'public');
            $venue->update(['plan_image_path' => $path]);
        }

        $this->syncSeatsForVenue($venue);

        return redirect()->route('admin.venues.index')->with('message', 'Lugar creado correctamente. Se han generado las butacas.');
    }

    public function edit(Venue $venue): View
    {
        $venue->load('sections', 'seats', 'layoutElements.seat');

        return view('admin.venues.edit', compact('venue'));
    }

    public function update(Request $request, Venue $venue): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:venues,slug,'.$venue->id],
            'address' => ['nullable', 'string', 'max:500'],
            'seat_rows' => ['required', 'integer', 'min:1', 'max:50'],
            'seat_columns' => ['required', 'integer', 'min:1', 'max:50'],
            'plan_image' => ['nullable', 'image', 'max:4096'],
            'sections' => ['nullable', 'array'],
            'sections.*.id' => ['nullable', 'integer', 'exists:sections,id'],
            'sections.*.name' => ['required_with:sections.*', 'string', 'max:255'],
            'sections.*.has_seats' => ['nullable', 'boolean'],
            'sections.*.capacity' => ['nullable', 'integer', 'min:0'],
            'sections.*.row_start' => ['nullable', 'integer', 'min:1', 'max:50'],
            'sections.*.row_end' => ['nullable', 'integer', 'min:1', 'max:50'],
            'sections.*.col_start' => ['nullable', 'integer', 'min:1', 'max:99'],
            'sections.*.col_end' => ['nullable', 'integer', 'min:1', 'max:99'],
            'sections.*.layout_color' => ['nullable', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value === null || $value === '') {
                    return;
                }
                $n = SectionLayoutColors::normalize((string) $value);
                if ($n === null || ! SectionLayoutColors::isAllowed($n)) {
                    $fail('El color de sección debe ser #RRGGBB y no puede ser negro ni rojo (reservados para no disponible).');
                }
            }],
        ]);

        $this->validateSectionSeatRangesNoOverlap(
            (int) $validated['seat_rows'],
            (int) $validated['seat_columns'],
            $request->input('sections', [])
        );

        $venue->name = $validated['name'];
        $venue->slug = $validated['slug'] ?? null;
        $venue->address = $validated['address'] ?? null;
        $venue->seat_rows = (int) $validated['seat_rows'];
        $venue->seat_columns = (int) $validated['seat_columns'];

        if ($request->hasFile('plan_image')) {
            $path = $request->file('plan_image')->store('venue-plans', 'public');
            $venue->plan_image_path = $path;
        }
        $venue->save();

        $this->syncSeatsForVenue($venue);
        $this->syncSectionsForVenue($venue, $request->input('sections', []));

        return redirect()->route('admin.venues.edit', $venue)->with('message', 'Lugar actualizado.');
    }

    public function destroy(Venue $venue): RedirectResponse
    {
        if ($venue->events()->exists()) {
            return redirect()->route('admin.venues.index')->with('message', 'No se puede eliminar: hay eventos asociados a este lugar.');
        }
        $venue->delete();

        return redirect()->route('admin.venues.index')->with('message', 'Lugar eliminado.');
    }

    public function layout(Venue $venue): JsonResponse
    {
        $venue->load('layoutElements.seat', 'seats');

        return response()->json([
            'canvas_width' => $venue->layout_canvas_width,
            'canvas_height' => $venue->layout_canvas_height,
            'elements' => $venue->layoutElements->map(fn (VenueLayoutElement $e) => $this->serializeLayoutElement($e))->values(),
            'seats' => $venue->seats->map(fn ($seat) => [
                'id' => $seat->id,
                'label' => $seat->display_label,
                'row' => $seat->row,
                'row_letter' => $seat->row_letter,
                'number' => $seat->number,
                'section_id' => $seat->section_id,
            ])->values(),
        ]);
    }

    public function saveLayout(Request $request, Venue $venue): JsonResponse
    {
        $validated = $request->validate([
            'elements' => ['required', 'array'],
            'elements.*.id' => ['nullable', 'integer'],
            'elements.*.type' => ['required', 'in:seat,stage,speaker'],
            'elements.*.seat_id' => ['nullable', 'integer'],
            'elements.*.x' => ['required', 'numeric', 'min:0', 'max:10000'],
            'elements.*.y' => ['required', 'numeric', 'min:0', 'max:10000'],
            'elements.*.w' => ['required', 'numeric', 'min:10', 'max:1000'],
            'elements.*.h' => ['required', 'numeric', 'min:10', 'max:1000'],
            'elements.*.rotation' => ['nullable', 'numeric', 'min:-360', 'max:360'],
            'elements.*.z_index' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'elements.*.meta' => ['nullable', 'array'],
            // Mapa seat_id => section_id (claves numéricas en JSON). No usar seat_sections.*: puede reindexar o fallar según el payload.
            'seat_sections' => ['nullable', 'array'],
            'section_colors' => ['nullable', 'array'],
            'section_colors.*' => ['nullable', 'string'],
            'canvas_width' => ['nullable', 'integer', 'min:200', 'max:4000'],
            'canvas_height' => ['nullable', 'integer', 'min:200', 'max:4000'],
        ]);

        $elements = $validated['elements'];
        $seatIds = collect($elements)
            ->where('type', VenueLayoutElement::TYPE_SEAT)
            ->pluck('seat_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($seatIds->count() !== $seatIds->unique()->count()) {
            return response()->json(['message' => 'No puedes repetir una butaca en el layout.'], 422);
        }

        $validSeatIds = $venue->seats()->whereIn('id', $seatIds->all())->pluck('id')->flip();
        foreach ($seatIds as $seatId) {
            if (! $validSeatIds->has($seatId)) {
                return response()->json(['message' => 'Una o más butacas no pertenecen a este lugar.'], 422);
            }
        }

        $seatSectionsInput = $validated['seat_sections'] ?? null;
        $seatSectionAssignments = null;
        if (is_array($seatSectionsInput) && $seatSectionsInput !== []) {
            $allVenueSeatIds = $venue->seats()->pluck('id')->map(fn ($id) => (int) $id)->values();
            $allVenueSeatIdsFlip = $allVenueSeatIds->flip();
            $sectionIdsFlip = $venue->sections()->pluck('id')->map(fn ($id) => (int) $id)->flip();
            $seatSectionAssignments = [];
            foreach ($seatSectionsInput as $seatIdRaw => $sectionIdRaw) {
                if (! is_numeric((string) $seatIdRaw)) {
                    return response()->json(['message' => 'La asignación de sección contiene una clave de butaca inválida.'], 422);
                }
                $seatId = (int) $seatIdRaw;
                if (! $allVenueSeatIdsFlip->has($seatId)) {
                    return response()->json(['message' => 'La asignación de sección contiene una butaca inválida.'], 422);
                }
                if ($sectionIdRaw === null || $sectionIdRaw === '') {
                    $seatSectionAssignments[$seatId] = null;

                    continue;
                }
                if (! is_numeric((string) $sectionIdRaw)) {
                    return response()->json(['message' => 'La asignación de sección contiene un id de sección inválido.'], 422);
                }
                $sectionId = (int) $sectionIdRaw;
                if (! $sectionIdsFlip->has($sectionId)) {
                    return response()->json(['message' => 'La asignación de sección contiene una sección inválida.'], 422);
                }
                $seatSectionAssignments[$seatId] = $sectionId;
            }
        }

        DB::transaction(function () use ($venue, $elements, $seatSectionAssignments, $validated) {
            $existingIds = $venue->layoutElements()->pluck('id')->all();
            $keepIds = [];
            $order = 0;

            foreach ($elements as $item) {
                $payload = [
                    'type' => $item['type'],
                    'seat_id' => $item['type'] === VenueLayoutElement::TYPE_SEAT ? ($item['seat_id'] ?? null) : null,
                    'x' => (float) $item['x'],
                    'y' => (float) $item['y'],
                    'w' => (float) $item['w'],
                    'h' => (float) $item['h'],
                    'rotation' => (float) ($item['rotation'] ?? 0),
                    'z_index' => (int) ($item['z_index'] ?? $order++),
                    'meta' => $item['meta'] ?? null,
                ];

                if (! empty($item['id'])) {
                    $element = $venue->layoutElements()->where('id', (int) $item['id'])->first();
                    if ($element) {
                        $element->update($payload);
                        $keepIds[] = $element->id;

                        continue;
                    }
                }

                $newElement = $venue->layoutElements()->create($payload);
                $keepIds[] = $newElement->id;
            }

            $deleteIds = array_diff($existingIds, $keepIds);
            if (! empty($deleteIds)) {
                $venue->layoutElements()->whereIn('id', $deleteIds)->delete();
            }

            if (is_array($seatSectionAssignments)) {
                foreach ($seatSectionAssignments as $seatId => $sectionId) {
                    $venue->seats()->where('id', (int) $seatId)->update(['section_id' => $sectionId !== null ? (int) $sectionId : null]);
                }
            }

            $cw = $validated['canvas_width'] ?? null;
            $ch = $validated['canvas_height'] ?? null;
            if ($cw !== null && $ch !== null) {
                $venue->update([
                    'layout_canvas_width' => (int) $cw,
                    'layout_canvas_height' => (int) $ch,
                ]);
            }

            $sectionColorsInput = $validated['section_colors'] ?? null;
            if (is_array($sectionColorsInput) && $sectionColorsInput !== []) {
                $sectionIdsFlip = $venue->sections()->pluck('id')->map(fn ($id) => (int) $id)->flip();
                foreach ($sectionColorsInput as $sectionIdRaw => $colorRaw) {
                    if (! is_numeric((string) $sectionIdRaw)) {
                        throw ValidationException::withMessages(['section_colors' => 'Clave de sección inválida en colores.']);
                    }
                    $sectionId = (int) $sectionIdRaw;
                    if (! $sectionIdsFlip->has($sectionId)) {
                        throw ValidationException::withMessages(['section_colors' => 'Un id de sección en colores no pertenece a este lugar.']);
                    }
                    if ($colorRaw === null || $colorRaw === '') {
                        Section::where('id', $sectionId)->where('venue_id', $venue->id)->update(['layout_color' => null]);

                        continue;
                    }
                    $n = SectionLayoutColors::normalize((string) $colorRaw);
                    if ($n === null || ! SectionLayoutColors::isAllowed($n)) {
                        throw ValidationException::withMessages(['section_colors' => 'Un color de sección no es válido (evita negro y rojo).']);
                    }
                    Section::where('id', $sectionId)->where('venue_id', $venue->id)->update(['layout_color' => $n]);
                }
            }
        });

        $venue->refresh();
        $venue->load('layoutElements.seat', 'seats', 'sections');

        return response()->json([
            'message' => 'Layout guardado.',
            'canvas_width' => $venue->layout_canvas_width,
            'canvas_height' => $venue->layout_canvas_height,
            'elements' => $venue->layoutElements->map(fn (VenueLayoutElement $e) => $this->serializeLayoutElement($e))->values(),
            'seats' => $venue->seats->map(fn ($seat) => [
                'id' => $seat->id,
                'label' => $seat->display_label,
                'row' => $seat->row,
                'row_letter' => $seat->row_letter,
                'number' => $seat->number,
                'section_id' => $seat->section_id,
            ])->values(),
            'sections' => $venue->sections->map(fn (Section $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'layout_color' => $s->layout_color,
            ])->values(),
        ]);
    }

    private function syncSeatsForVenue(Venue $venue): void
    {
        $rows = (int) $venue->seat_rows;
        $cols = (int) $venue->seat_columns;

        // Eliminar butacas fuera del nuevo grid
        $venue->seats()->where(function ($q) use ($rows, $cols) {
            $q->where('row', '>', $rows)->orWhere('number', '>', $cols);
        })->delete();

        for ($row = 1; $row <= $rows; $row++) {
            for ($num = 1; $num <= $cols; $num++) {
                $rowLetter = $row >= 1 && $row <= 26 ? chr(64 + $row) : (string) $row;
                $venue->seats()->updateOrCreate(
                    ['row' => $row, 'number' => $num],
                    ['label' => $rowLetter.'-'.$num]
                );
            }
        }
    }

    private function syncSectionsForVenue(Venue $venue, array $sectionsInput): void
    {
        $idsToKeep = [];
        $sortOrder = 0;

        // Solo anular section_id en butacas cubiertas por los rectángulos del formulario.
        // Así no se borran asignaciones hechas en el editor de layout (butacas fuera de esos rangos).
        $seatIdsToReset = $this->seatIdsCoveredBySubmittedSectionRanges($venue, $sectionsInput);
        if ($seatIdsToReset->isNotEmpty()) {
            $venue->seats()->whereIn('id', $seatIdsToReset->all())->update(['section_id' => null]);
        }

        foreach ($sectionsInput as $input) {
            if (empty($input['name'] ?? '')) {
                continue;
            }
            $id = isset($input['id']) && $input['id'] !== '' ? (int) $input['id'] : null;
            $hasSeats = ! empty($input['has_seats']);
            $rowStart = isset($input['row_start']) && $input['row_start'] !== '' ? (int) $input['row_start'] : null;
            $rowEnd = isset($input['row_end']) && $input['row_end'] !== '' ? (int) $input['row_end'] : null;
            if ($rowStart !== null && $rowEnd !== null && $rowStart > $rowEnd) {
                [$rowStart, $rowEnd] = [$rowEnd, $rowStart];
            }
            $colStart = isset($input['col_start']) && $input['col_start'] !== '' ? (int) $input['col_start'] : null;
            $colEnd = isset($input['col_end']) && $input['col_end'] !== '' ? (int) $input['col_end'] : null;
            if ($colStart !== null && $colEnd !== null && $colStart > $colEnd) {
                [$colStart, $colEnd] = [$colEnd, $colStart];
            }
            if ($id) {
                $section = Section::where('id', $id)->where('venue_id', $venue->id)->first();
                if (! $section) {
                    continue;
                }
            } else {
                $section = new Section(['venue_id' => $venue->id]);
            }
            $section->name = $input['name'];
            $section->sort_order = $sortOrder++;
            $section->has_seats = $hasSeats;
            $section->capacity = $hasSeats ? null : (isset($input['capacity']) && $input['capacity'] !== '' ? (int) $input['capacity'] : null);
            $section->row_start = $hasSeats ? $rowStart : null;
            $section->row_end = $hasSeats ? $rowEnd : null;
            $section->col_start = $hasSeats ? $colStart : null;
            $section->col_end = $hasSeats ? $colEnd : null;
            $lcRaw = $input['layout_color'] ?? null;
            if ($lcRaw === null || $lcRaw === '') {
                $section->layout_color = null;
            } else {
                $n = SectionLayoutColors::normalize((string) $lcRaw);
                $section->layout_color = ($n !== null && SectionLayoutColors::isAllowed($n)) ? $n : null;
            }
            $section->save();
            $idsToKeep[] = $section->id;

            if ($hasSeats && $rowStart !== null && $rowEnd !== null) {
                $r1 = min($rowStart, $rowEnd);
                $r2 = max($rowStart, $rowEnd);
                $q = $venue->seats()->whereBetween('row', [$r1, $r2]);
                if ($colStart !== null && $colEnd !== null) {
                    $c1 = min($colStart, $colEnd);
                    $c2 = max($colStart, $colEnd);
                    $q->whereBetween('number', [$c1, $c2]);
                }
                $q->update(['section_id' => $section->id]);
            }
        }

        $venue->sections()->whereNotIn('id', $idsToKeep)->delete();
    }

    /**
     * Butacas (ids) que caen dentro de algún rectángulo fila/columna definido en el payload de secciones.
     *
     * @param  array<int, array<string, mixed>>  $sectionsInput
     */
    private function seatIdsCoveredBySubmittedSectionRanges(Venue $venue, array $sectionsInput): Collection
    {
        $maxRow = (int) $venue->seat_rows;
        $maxCol = (int) $venue->seat_columns;
        $rects = [];
        foreach ($sectionsInput as $input) {
            $rect = $this->parseSectionSeatRectangle($input, $maxRow, $maxCol);
            if ($rect !== null) {
                $rects[] = $rect;
            }
        }
        if ($rects === []) {
            return collect();
        }

        return $venue->seats()
            ->where(function ($outer) use ($rects): void {
                foreach ($rects as $i => $rect) {
                    $closure = function ($q) use ($rect): void {
                        $q->whereBetween('row', [$rect['r1'], $rect['r2']])
                            ->whereBetween('number', [$rect['c1'], $rect['c2']]);
                    };
                    if ($i === 0) {
                        $outer->where($closure);
                    } else {
                        $outer->orWhere($closure);
                    }
                }
            })
            ->pluck('id');
    }

    /**
     * @param  array<int, array<string, mixed>>  $sectionsInput
     */
    private function validateSectionSeatRangesNoOverlap(int $venueSeatRows, int $venueSeatColumns, array $sectionsInput): void
    {
        $rects = [];
        foreach ($sectionsInput as $input) {
            $rect = $this->parseSectionSeatRectangle($input, $venueSeatRows, $venueSeatColumns);
            if ($rect !== null) {
                $rects[] = $rect;
            }
        }
        $n = count($rects);
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                if ($this->sectionRectanglesOverlap($rects[$i], $rects[$j])) {
                    throw ValidationException::withMessages([
                        'sections' => ['Las secciones con butacas no pueden solapar el mismo bloque de filas y columnas. Ajusta los rangos.'],
                    ]);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{r1: int, r2: int, c1: int, c2: int}|null
     */
    private function parseSectionSeatRectangle(array $input, int $maxRow, int $maxCol): ?array
    {
        if (empty($input['name'] ?? '')) {
            return null;
        }
        if (empty($input['has_seats'] ?? false)) {
            return null;
        }
        $rowStart = isset($input['row_start']) && $input['row_start'] !== '' ? (int) $input['row_start'] : null;
        $rowEnd = isset($input['row_end']) && $input['row_end'] !== '' ? (int) $input['row_end'] : null;
        if ($rowStart === null || $rowEnd === null) {
            return null;
        }
        $maxRow = max(1, $maxRow);
        $maxCol = max(1, $maxCol);
        $r1 = max(1, min($rowStart, $rowEnd));
        $r2 = min($maxRow, max($rowStart, $rowEnd));
        if ($r1 > $r2) {
            return null;
        }
        $colStart = isset($input['col_start']) && $input['col_start'] !== '' ? (int) $input['col_start'] : null;
        $colEnd = isset($input['col_end']) && $input['col_end'] !== '' ? (int) $input['col_end'] : null;
        if ($colStart !== null && $colEnd !== null) {
            $c1 = max(1, min($colStart, $colEnd));
            $c2 = min($maxCol, max($colStart, $colEnd));
        } else {
            $c1 = 1;
            $c2 = $maxCol;
        }
        if ($c1 > $c2) {
            return null;
        }

        return ['r1' => $r1, 'r2' => $r2, 'c1' => $c1, 'c2' => $c2];
    }

    /**
     * @param  array{r1: int, r2: int, c1: int, c2: int}  $a
     * @param  array{r1: int, r2: int, c1: int, c2: int}  $b
     */
    private function sectionRectanglesOverlap(array $a, array $b): bool
    {
        return max($a['r1'], $b['r1']) <= min($a['r2'], $b['r2'])
            && max($a['c1'], $b['c1']) <= min($a['c2'], $b['c2']);
    }

    private function serializeLayoutElement(VenueLayoutElement $e): array
    {
        return [
            'id' => $e->id,
            'type' => $e->type,
            'seat_id' => $e->seat_id,
            'x' => (float) $e->x,
            'y' => (float) $e->y,
            'w' => (float) $e->w,
            'h' => (float) $e->h,
            'rotation' => (float) $e->rotation,
            'z_index' => (int) $e->z_index,
            'meta' => $e->meta ?? [],
            'seat' => $e->seat ? [
                'id' => $e->seat->id,
                'label' => $e->seat->display_label,
                'row' => $e->seat->row,
                'number' => $e->seat->number,
                'section_id' => $e->seat->section_id,
            ] : null,
        ];
    }
}
