<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeaturedVideo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class FeaturedVideoController extends Controller
{
    public function index(): View
    {
        $videos = FeaturedVideo::orderBy('sort_order')->orderBy('id')->get();

        return view('admin.featured-videos.index', compact('videos'));
    }

    public function create(): View|RedirectResponse
    {
        if (FeaturedVideo::count() >= FeaturedVideo::MAX_ON_HOME) {
            return redirect()->route('admin.featured-videos.index')
                ->with('error', 'Solo se pueden publicar '.FeaturedVideo::MAX_ON_HOME.' videos. Edita o elimina uno existente.');
        }

        return view('admin.featured-videos.create');
    }

    public function store(Request $request): RedirectResponse
    {
        if (FeaturedVideo::count() >= FeaturedVideo::MAX_ON_HOME) {
            return redirect()->route('admin.featured-videos.index')
                ->with('error', 'Solo se pueden publicar '.FeaturedVideo::MAX_ON_HOME.' videos. Edita o elimina uno existente.');
        }

        $validated = $this->validateVideo($request);

        $path = null;
        if ($request->hasFile('thumbnail') && $request->file('thumbnail')->isValid()) {
            try {
                Storage::disk('public')->makeDirectory('featured-videos');
                $path = $request->file('thumbnail')->store('featured-videos', 'public');
            } catch (\Throwable $e) {
                return redirect()->back()->withInput()->withErrors([
                    'thumbnail' => 'Error al guardar la miniatura: '.$e->getMessage(),
                ]);
            }
        }

        $maxOrder = FeaturedVideo::max('sort_order') ?? 0;

        FeaturedVideo::create([
            'title' => $validated['title'] !== '' ? $validated['title'] : null,
            'video_url' => FeaturedVideo::canonicalizeFacebookUrl($validated['video_url']),
            'thumbnail_path' => $path,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()->route('admin.featured-videos.index')->with('message', 'Video añadido. Aparecerá en la página principal.');
    }

    public function edit(FeaturedVideo $featured_video): View
    {
        return view('admin.featured-videos.edit', ['video' => $featured_video]);
    }

    public function update(Request $request, FeaturedVideo $featured_video): RedirectResponse
    {
        $validated = $this->validateVideo($request, thumbnailRequired: false);

        $data = [
            'title' => $validated['title'] !== '' ? $validated['title'] : null,
            'video_url' => FeaturedVideo::canonicalizeFacebookUrl($validated['video_url']),
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->hasFile('thumbnail') && $request->file('thumbnail')->isValid()) {
            try {
                Storage::disk('public')->makeDirectory('featured-videos');
                if ($featured_video->thumbnail_path) {
                    Storage::disk('public')->delete($featured_video->thumbnail_path);
                }
                $data['thumbnail_path'] = $request->file('thumbnail')->store('featured-videos', 'public');
            } catch (\Throwable $e) {
                return redirect()->back()->withInput()->withErrors([
                    'thumbnail' => 'Error al guardar la miniatura: '.$e->getMessage(),
                ]);
            }
        }

        $featured_video->update($data);

        return redirect()->route('admin.featured-videos.index')->with('message', 'Video actualizado.');
    }

    public function destroy(FeaturedVideo $featured_video): RedirectResponse
    {
        if ($featured_video->thumbnail_path) {
            Storage::disk('public')->delete($featured_video->thumbnail_path);
        }
        $featured_video->delete();

        return redirect()->route('admin.featured-videos.index')->with('message', 'Video eliminado.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:featured_videos,id'],
        ]);

        foreach ($request->input('order') as $position => $id) {
            FeaturedVideo::where('id', $id)->update(['sort_order' => $position]);
        }

        return redirect()->route('admin.featured-videos.index')->with('message', 'Orden actualizado.');
    }

    /**
     * @return array{title: string, video_url: string}
     */
    private function validateVideo(Request $request, bool $thumbnailRequired = false): array
    {
        $thumbnailRules = ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'];
        if ($thumbnailRequired) {
            $thumbnailRules[0] = 'required';
        }

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'video_url' => [
                'required',
                'string',
                'max:500',
                'url',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (FeaturedVideo::isShareUrl($value)) {
                        $fail('Usa el enlace del reel (facebook.com/reel/…), no el de Compartir (facebook.com/share/…). Abre el video en Facebook y copia la URL de la barra de direcciones.');

                        return;
                    }
                    if (! FeaturedVideo::isAcceptedFacebookUrl($value)) {
                        $fail('La URL debe ser un video o reel público de Facebook (facebook.com/reel/…, /videos/…, /watch/?v=… o fb.watch/…).');
                    }
                },
            ],
            'thumbnail' => $thumbnailRules,
            'is_active' => ['nullable', 'boolean'],
        ], [
            'video_url.required' => 'Pega la URL del reel o video de Facebook.',
            'video_url.url' => 'La URL no es válida.',
            'thumbnail.image' => 'El archivo debe ser una imagen (JPG, PNG o WebP).',
            'thumbnail.mimes' => 'Formato no válido. Usa JPG, PNG o WebP.',
            'thumbnail.max' => 'La miniatura no puede superar 5 MB.',
        ]);

        $validated['title'] = trim((string) ($validated['title'] ?? ''));

        return $validated;
    }
}
