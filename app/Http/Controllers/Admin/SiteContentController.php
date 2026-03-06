<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteContentController extends Controller
{
    public function quienesSomos(): View
    {
        $block = SiteContent::firstOrCreate(
            ['key' => SiteContent::KEY_QUIENES_SOMOS],
            [
                'title' => 'QUIÉNES SOMOS',
                'content' => "NOVA es tu plataforma para descubrir eventos y reservar tickets de forma rápida y segura.\nConectamos organizadores con el público: elige tu evento, reserva de 1 a 4 entradas con un código único de pago y recibe tus tickets por correo.\n\nSimple, transparente y pensado para que no te pierdas nada.",
            ]
        );
        return view('admin.site-content.quienes-somos', compact('block'));
    }

    public function updateQuienesSomos(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:10000'],
        ]);

        $block = SiteContent::firstOrCreate(
            ['key' => SiteContent::KEY_QUIENES_SOMOS],
            ['title' => '', 'content' => '']
        );
        $block->update([
            'title' => $request->input('title'),
            'content' => $request->input('content'),
        ]);

        return redirect()->route('admin.site-content.quienes-somos')->with('message', 'Contenido de Quiénes somos actualizado.');
    }

    public function hero(): View
    {
        $block = SiteContent::firstOrCreate(
            ['key' => SiteContent::KEY_HERO],
            [
                'title' => 'Tus entradas. Tu experiencia.',
                'content' => 'ENTRAR',
            ]
        );
        return view('admin.site-content.hero', compact('block'));
    }

    public function updateHero(Request $request): RedirectResponse
    {
        $request->validate([
            'subtitle' => ['required', 'string', 'max:255'],
            'cta_text' => ['required', 'string', 'max:100'],
        ]);

        $block = SiteContent::firstOrCreate(
            ['key' => SiteContent::KEY_HERO],
            ['title' => '', 'content' => '']
        );
        $block->update([
            'title' => $request->input('subtitle'),
            'content' => $request->input('cta_text'),
        ]);

        return redirect()->route('admin.site-content.hero')->with('message', 'Texto del hero actualizado.');
    }
}
