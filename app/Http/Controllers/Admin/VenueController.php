<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        return view('admin.venues.edit', compact('venue'));
    }

    public function update(Request $request, Venue $venue): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:venues,slug,' . $venue->id],
            'address' => ['nullable', 'string', 'max:500'],
            'seat_rows' => ['required', 'integer', 'min:1', 'max:50'],
            'seat_columns' => ['required', 'integer', 'min:1', 'max:50'],
            'plan_image' => ['nullable', 'image', 'max:4096'],
        ]);

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

        return redirect()->route('admin.venues.index')->with('message', 'Lugar actualizado.');
    }

    public function destroy(Venue $venue): RedirectResponse
    {
        if ($venue->events()->exists()) {
            return redirect()->route('admin.venues.index')->with('message', 'No se puede eliminar: hay eventos asociados a este lugar.');
        }
        $venue->delete();
        return redirect()->route('admin.venues.index')->with('message', 'Lugar eliminado.');
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
                    ['label' => $rowLetter . '-' . $num]
                );
            }
        }
    }
}
