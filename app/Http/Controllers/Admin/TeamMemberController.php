<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TeamMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TeamMemberController extends Controller
{
    public function index(): View
    {
        $members = TeamMember::orderBy('sort_order')->orderBy('id')->get();
        return view('admin.team-members.index', compact('members'));
    }

    public function create(): View
    {
        return view('admin.team-members.create');
    }

    public function bulkCreate(): View
    {
        return view('admin.team-members.bulk-create');
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:20'],
            'photos.*' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ], [
            'photos.required' => 'Selecciona al menos una foto.',
            'photos.min' => 'Selecciona al menos una foto.',
            'photos.max' => 'Máximo 20 fotos a la vez.',
            'photos.*.image' => 'Cada archivo debe ser una imagen (JPG, PNG o WebP).',
            'photos.*.max' => 'Cada foto debe pesar como máximo 5 MB.',
        ]);

        $files = $request->file('photos');
        $maxOrder = TeamMember::max('sort_order') ?? 0;
        $created = 0;

        try {
            Storage::disk('public')->makeDirectory('team-members');
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors(['photos' => 'Error al crear la carpeta: '.$e->getMessage()]);
        }

        foreach ($files as $index => $file) {
            if (! $file->isValid()) {
                continue;
            }
            try {
                $path = $file->store('team-members', 'public');
                TeamMember::create([
                    'name' => null,
                    'role' => null,
                    'photo_path' => $path,
                    'sort_order' => $maxOrder + 1 + $created,
                ]);
                $created++;
            } catch (\Throwable $e) {
                return redirect()->back()->withErrors(['photos' => 'Error en la foto '.($index + 1).': '.$e->getMessage()]);
            }
        }

        $message = $created === 1 ? '1 integrante añadido.' : $created.' integrantes añadidos.';
        return redirect()->route('admin.team-members.index')->with('message', $message);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'photo' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:5120',
            ],
        ], [
            'photo.required' => 'Debes seleccionar una foto.',
            'photo.image' => 'El archivo debe ser una imagen (JPG, PNG o similar).',
            'photo.mimes' => 'Formato no válido. Usa JPG, PNG o WebP.',
            'photo.max' => 'La foto no puede superar 5 MB. Redúcela o comprime la imagen.',
        ]);

        if (! $request->hasFile('photo') || ! $request->file('photo')->isValid()) {
            return redirect()->back()->withInput()->withErrors([
                'photo' => 'No se pudo subir el archivo. Comprueba que no supere 5 MB y que sea una imagen.',
            ]);
        }

        $maxOrder = TeamMember::max('sort_order') ?? 0;

        try {
            Storage::disk('public')->makeDirectory('team-members');
            $path = $request->file('photo')->store('team-members', 'public');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->withErrors([
                'photo' => 'Error al guardar la foto: '.$e->getMessage(),
            ]);
        }

        TeamMember::create([
            'name' => $request->input('name') ? trim($request->input('name')) : null,
            'role' => $request->input('role') ? trim($request->input('role')) : null,
            'photo_path' => $path,
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()->route('admin.team-members.index')->with('message', 'Integrante añadido.');
    }

    public function edit(TeamMember $team_member): View
    {
        return view('admin.team-members.edit', compact('team_member'));
    }

    public function update(Request $request, TeamMember $team_member): RedirectResponse
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'photo' => [
                'nullable',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:5120',
            ],
        ], [
            'photo.image' => 'El archivo debe ser una imagen (JPG, PNG o similar).',
            'photo.mimes' => 'Formato no válido. Usa JPG, PNG o WebP.',
            'photo.max' => 'La foto no puede superar 5 MB.',
        ]);

        $data = [
            'name' => $request->input('name') ? trim($request->input('name')) : null,
            'role' => $request->input('role') ? trim($request->input('role')) : null,
        ];

        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            try {
                Storage::disk('public')->delete($team_member->photo_path);
                $data['photo_path'] = $request->file('photo')->store('team-members', 'public');
            } catch (\Throwable $e) {
                return redirect()->back()->withInput()->withErrors([
                    'photo' => 'Error al guardar la foto: '.$e->getMessage(),
                ]);
            }
        }

        $team_member->update($data);

        return redirect()->route('admin.team-members.index')->with('message', 'Integrante actualizado.');
    }

    public function destroy(TeamMember $team_member): RedirectResponse
    {
        Storage::disk('public')->delete($team_member->photo_path);
        $team_member->delete();
        return redirect()->route('admin.team-members.index')->with('message', 'Integrante eliminado.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:team_members,id'],
        ]);

        foreach ($request->input('order') as $position => $id) {
            TeamMember::where('id', $id)->update(['sort_order' => $position]);
        }

        return redirect()->route('admin.team-members.index')->with('message', 'Orden actualizado.');
    }
}
