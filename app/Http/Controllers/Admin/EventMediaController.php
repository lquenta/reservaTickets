<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventMedia;
use App\Models\FeaturedVideo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EventMediaController extends Controller
{
    public function store(Request $request, Event $event): RedirectResponse
    {
        $mode = $request->input('mode');

        if ($mode === 'photo') {
            return $this->storeUpload($request, $event, EventMedia::KIND_PHOTO);
        }

        if ($mode === 'video') {
            return $this->storeUpload($request, $event, EventMedia::KIND_VIDEO);
        }

        if ($mode === 'facebook') {
            return $this->storeFacebook($request, $event);
        }

        return redirect()->back()->withErrors(['mode' => 'Elige foto, video o un enlace de Facebook.']);
    }

    public function destroy(Event $event, EventMedia $event_media): RedirectResponse
    {
        abort_unless($event_media->event_id === $event->id, 404);
        $event_media->delete();

        return redirect()->route('admin.events.show', $event)->with('message', 'Pieza eliminada de la galería.');
    }

    private function storeUpload(Request $request, Event $event, string $kind): RedirectResponse
    {
        $rules = $kind === EventMedia::KIND_PHOTO
            ? ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120']
            : ['required', 'file', 'mimetypes:video/mp4,video/webm', 'max:20480'];

        $request->validate([
            'file' => $rules,
        ], [
            'file.required' => $kind === EventMedia::KIND_PHOTO ? 'Selecciona una foto.' : 'Selecciona un video.',
            'file.image' => 'El archivo debe ser una imagen (JPG, PNG o WebP).',
            'file.mimes' => 'Formato no válido. Usa JPG, PNG o WebP.',
            'file.mimetypes' => 'El video debe ser MP4 o WebM.',
            'file.max' => $kind === EventMedia::KIND_PHOTO
                ? 'La foto no puede superar 5 MB.'
                : 'El video no puede superar 20 MB.',
        ]);

        $directory = 'event-media/'.$event->id;
        Storage::disk('public')->makeDirectory($directory);
        $path = $request->file('file')->store($directory, 'public');

        $event->media()->create([
            'kind' => $kind,
            'source' => EventMedia::SOURCE_UPLOAD,
            'file_path' => $path,
            'sort_order' => $this->nextSortOrder($event),
        ]);

        $label = $kind === EventMedia::KIND_PHOTO ? 'Foto añadida a la galería.' : 'Video añadido a la galería.';

        return redirect()->route('admin.events.show', $event)->with('message', $label);
    }

    private function storeFacebook(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'external_url' => [
                'required',
                'string',
                'max:500',
                'url',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (FeaturedVideo::isShareUrl(is_string($value) ? $value : null)) {
                        $fail('Usa el enlace del reel o de la foto, no el de Compartir (facebook.com/share/…). Abre la publicación en Facebook y copia la URL de la barra de direcciones.');

                        return;
                    }
                    if (! EventMedia::classifyFacebookUrl(is_string($value) ? $value : null)) {
                        $fail('La URL debe ser un reel, un video o una foto pública de Facebook.');
                    }
                },
            ],
            'thumbnail' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ], [
            'external_url.required' => 'Pega el enlace de Facebook.',
            'external_url.url' => 'La URL no es válida.',
            'thumbnail.image' => 'La miniatura debe ser una imagen (JPG, PNG o WebP).',
            'thumbnail.max' => 'La miniatura no puede superar 5 MB.',
        ]);

        $classified = EventMedia::classifyFacebookUrl($validated['external_url']);
        $thumbnail = null;
        if ($request->hasFile('thumbnail') && $request->file('thumbnail')->isValid()) {
            $directory = 'event-media/'.$event->id;
            Storage::disk('public')->makeDirectory($directory);
            $thumbnail = $request->file('thumbnail')->store($directory, 'public');
        }

        $event->media()->create([
            'kind' => $classified['kind'],
            'source' => EventMedia::SOURCE_FACEBOOK,
            'external_url' => $classified['url'],
            'thumbnail_path' => $thumbnail,
            'sort_order' => $this->nextSortOrder($event),
        ]);

        return redirect()->route('admin.events.show', $event)->with('message', 'Enlace de Facebook añadido a la galería.');
    }

    private function nextSortOrder(Event $event): int
    {
        return ((int) $event->media()->max('sort_order')) + 1;
    }
}
