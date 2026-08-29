<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Section;
use App\Services\SeatLabelPdfService;
use App\Support\SeatLabelLayout;
use App\Support\SectionLayoutColors;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SeatLabelController extends Controller
{
    public function __construct(private readonly SeatLabelPdfService $seatLabels) {}

    public function create(Event $event): View|RedirectResponse
    {
        if (! $event->venue_id) {
            return redirect()
                ->route('admin.events.show', $event)
                ->with('error', 'Este evento no tiene un lugar con butacas para generar etiquetas.');
        }

        $payload = $this->seatLabels->previewPayload($event);

        return view('admin.events.seat-labels', compact('event', 'payload'));
    }

    public function download(Request $request, Event $event): Response|RedirectResponse
    {
        if (! $event->venue_id) {
            return redirect()
                ->route('admin.events.show', $event)
                ->with('error', 'Este evento no tiene un lugar con butacas para generar etiquetas.');
        }

        $options = $this->validatedOptions($request, $event);
        $data = $this->seatLabels->buildPdfData($event, $options);

        if ($data['pages']->isEmpty()) {
            return redirect()
                ->route('admin.events.seat-labels.create', $event)
                ->with('error', 'No hay asientos para generar etiquetas.');
        }

        $pdf = Pdf::loadView('admin.events.pdf.seat-labels', $data);
        $pdf->setPaper('a4', $data['orientation']);

        $filename = 'etiquetas-'.Str::slug($event->name).'-'.now()->format('Y-m-d').'.pdf';
        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
        ]);
    }

    /**
     * @return array{
     *     per_page: int,
     *     section_id: int|null,
     *     color_mode: string,
     *     custom_color: string|null,
     *     overlay_opacity: int,
     *     section_colors: array<int, string>,
     *     orientation: string
     * }
     */
    private function validatedOptions(Request $request, Event $event): array
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:'.SeatLabelLayout::MIN_PER_PAGE, 'max:'.SeatLabelLayout::MAX_PER_PAGE],
            'section_id' => ['nullable', 'integer'],
            'color_mode' => ['nullable', 'in:sector,custom'],
            'custom_color' => ['nullable', 'string', 'max:7'],
            'overlay_opacity' => ['nullable', 'integer', 'min:'.SeatLabelLayout::MIN_OPACITY, 'max:'.SeatLabelLayout::MAX_OPACITY],
            'section_colors' => ['nullable', 'array'],
            'section_colors.*' => ['nullable', 'string', 'max:7'],
            'orientation' => ['nullable', 'in:portrait,landscape'],
        ]);

        $sectionId = array_key_exists('section_id', $validated) && $validated['section_id'] !== null && $validated['section_id'] !== ''
            ? (int) $validated['section_id']
            : null;

        if ($sectionId !== null && $sectionId !== 0) {
            $belongs = Section::query()
                ->where('venue_id', $event->venue_id)
                ->whereKey($sectionId)
                ->exists();
            if (! $belongs) {
                $sectionId = null;
            }
        }

        $sectionColors = [];
        foreach ($validated['section_colors'] ?? [] as $id => $hex) {
            $normalized = SectionLayoutColors::normalize(is_string($hex) ? $hex : null);
            if ($normalized !== null) {
                $sectionColors[(int) $id] = $normalized;
            }
        }

        return [
            'per_page' => SeatLabelLayout::normalizePerPage((int) ($validated['per_page'] ?? SeatLabelLayout::DEFAULT_PER_PAGE)),
            'section_id' => $sectionId,
            'color_mode' => ($validated['color_mode'] ?? 'sector') === 'custom' ? 'custom' : 'sector',
            'custom_color' => SectionLayoutColors::normalize($validated['custom_color'] ?? null) ?? '#2563EB',
            'overlay_opacity' => SeatLabelLayout::normalizeOpacity((int) ($validated['overlay_opacity'] ?? SeatLabelLayout::DEFAULT_OPACITY)),
            'section_colors' => $sectionColors,
            'orientation' => ($validated['orientation'] ?? 'portrait') === 'landscape' ? 'landscape' : 'portrait',
        ];
    }
}
