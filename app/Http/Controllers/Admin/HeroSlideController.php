<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeroSetting;
use App\Models\HeroSlide;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class HeroSlideController extends Controller
{
    private function getHeroSetting(): HeroSetting
    {
        $setting = HeroSetting::first();
        if (! $setting) {
            $setting = HeroSetting::create(['type' => HeroSetting::TYPE_SLIDER]);
        }
        return $setting;
    }

    public function index(): View
    {
        $heroSetting = $this->getHeroSetting();
        $slides = HeroSlide::orderBy('sort_order')->orderBy('id')->get();
        return view('admin.hero-slides.index', compact('heroSetting', 'slides'));
    }

    public function store(Request $request): RedirectResponse
    {
        $heroSetting = $this->getHeroSetting();
        $heroSetting->update(['type' => HeroSetting::TYPE_SLIDER]);

        $request->validate([
            'image' => ['required', 'image', 'max:5120'],
        ]);

        $maxOrder = HeroSlide::max('sort_order') ?? 0;
        $path = $request->file('image')->store('hero-slides', 'public');
        HeroSlide::create([
            'image_path' => $path,
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()->route('admin.hero-slides.index')->with('message', 'Imagen añadida al slider del inicio.');
    }

    public function destroy(HeroSlide $hero_slide): RedirectResponse
    {
        Storage::disk('public')->delete($hero_slide->image_path);
        $hero_slide->delete();
        return redirect()->route('admin.hero-slides.index')->with('message', 'Imagen eliminada del slider.');
    }

    public function storeVideo(Request $request): RedirectResponse
    {
        $request->validate([
            'video_url' => ['nullable', 'required_without:video', 'url', 'max:500'],
            'video' => ['nullable', 'required_without:video_url', 'file', 'mimetypes:video/mp4,video/webm,video/ogg', 'max:102400'], // 100MB
        ], [
            'video.mimetypes' => 'El video debe ser MP4, WebM u OGG.',
            'video_url.required_without' => 'Indica la URL del video o sube un archivo.',
            'video.required_without' => 'Sube un archivo de video o indica una URL.',
        ]);

        $heroSetting = $this->getHeroSetting();
        $heroSetting->update(['type' => HeroSetting::TYPE_VIDEO]);

        if ($request->hasFile('video')) {
            if ($heroSetting->video_path) {
                Storage::disk('public')->delete($heroSetting->video_path);
            }
            $path = $request->file('video')->store('hero-video', 'public');
            $heroSetting->update(['video_path' => $path, 'video_url' => null]);
            return redirect()->route('admin.hero-slides.index')->with('message', 'Video subido. El hero usará este video como fondo.');
        }

        if ($request->filled('video_url')) {
            if ($heroSetting->video_path) {
                Storage::disk('public')->delete($heroSetting->video_path);
            }
            $heroSetting->update(['video_url' => $request->input('video_url'), 'video_path' => null]);
            return redirect()->route('admin.hero-slides.index')->with('message', 'Enlace de video guardado. El hero usará este video como fondo.');
        }

        return redirect()->route('admin.hero-slides.index')->with('error', 'Indica una URL de video o sube un archivo.');
    }

    public function useSlider(Request $request): RedirectResponse
    {
        $heroSetting = $this->getHeroSetting();
        if ($heroSetting->video_path) {
            Storage::disk('public')->delete($heroSetting->video_path);
        }
        $heroSetting->update([
            'type' => HeroSetting::TYPE_SLIDER,
            'video_url' => null,
            'video_path' => null,
        ]);
        return redirect()->route('admin.hero-slides.index')->with('message', 'Fondo en modo fotos (slider). Añade imágenes si quieres.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:hero_slides,id'],
        ]);

        foreach ($request->input('order') as $position => $id) {
            HeroSlide::where('id', $id)->update(['sort_order' => $position]);
        }

        return redirect()->route('admin.hero-slides.index')->with('message', 'Orden actualizado.');
    }
}
