<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\TicketTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketTemplateController extends Controller
{
    public function edit(Event $event): View
    {
        $template = $event->ticketTemplate ?? new TicketTemplate(['event_id' => $event->id, 'design' => TicketTemplate::defaultDesign(), 'price' => 0]);
        return view('admin.ticket-templates.edit', compact('event', 'template'));
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'design_title' => ['nullable', 'string', 'max:255'],
            'design_subtitle' => ['nullable', 'string', 'max:500'],
            'design_price_label' => ['nullable', 'string', 'max:100'],
            'design_seat_label' => ['nullable', 'string', 'max:100'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $design = array_merge(TicketTemplate::defaultDesign(), [
            'title' => $validated['design_title'] ?? '',
            'subtitle' => $validated['design_subtitle'] ?? '',
            'price_label' => $validated['design_price_label'] ?? 'Precio',
            'seat_label' => $validated['design_seat_label'] ?? 'Butaca',
        ]);

        $template = $event->ticketTemplate ?? new TicketTemplate(['event_id' => $event->id]);
        $template->design = $design;
        $template->price = $validated['price'] ?? 0;
        $template->event_id = $event->id;
        $template->save();

        return redirect()->route('admin.events.index')->with('message', 'Plantilla de ticket guardada.');
    }
}
