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
        $isPhoto = $kind === EventMedia::KIND_PHOTO;
        $maxCount = $isPhoto ? 30 : 8;
        $fileRules = $isPhoto
            ? ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120']
            : ['required', 'file', 'mimetypes:video/mp4,video/webm', 'max:20480'];

        $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:'.$maxCount],
            'files.*' => $fileRules,
        ], [
            'files.required' => $isPhoto ? 'Selecciona al menos una foto.' : 'Selecciona al menos un video.',
            'files.max' => $isPhoto
                ? 'Puedes subir hasta 30 fotos a la vez.'
                : 'Puedes subir hasta 8 videos a la vez.',
            'files.*.image' => 'Cada archivo debe ser una imagen (JPG, PNG o WebP).',
            'files.*.mimes' => 'Formato no válido. Usa JPG, PNG o WebP.',
            'files.*.mimetypes' => 'Cada video debe ser MP4 o WebM.',
            'files.*.max' => $isPhoto
                ? 'Cada foto no puede superar 5 MB.'
                : 'Cada video no puede superar 20 MB.',
        ]);

        $directory = 'event-media/'.$event->id;
        Storage::disk('public')->makeDirectory($directory);
        $order = $this->nextSortOrder($event);
        $count = 0;

        foreach ($request->file('files', []) as $file) {
            $event->media()->create([
                'kind' => $kind,
                'source' => EventMedia::SOURCE_UPLOAD,
                'file_path' => $file->store($directory, 'public'),
                'sort_order' => $order + $count,
            ]);
            $count++;
        }

        $label = $isPhoto
            ? ($count === 1 ? 'Foto añadida a la galería.' : $count.' fotos añadidas a la galería.')
            : ($count === 1 ? 'Video añadido a la galería.' : $count.' videos añadidos a la galería.');

        return redirect()->route('admin.events.show', $event)->with('message', $label);
    }

    private function storeFacebook(Request $request, Event $event): RedirectResponse
    {
        $raw = (string) $request->input('external_urls', $request->input('external_url', ''));
        $urls = array_values(array_filter(array_map(
            trim(...),
            preg_split('/\r\n|\r|\n/', $raw) ?: []
        ), fn (string $line) => $line !== ''));
        $request->merge(['facebook_links' => $urls]);

        $request->validate([
            'facebook_links' => ['required', 'array', 'min:1', 'max:20'],
            'facebook_links.*' => [
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
                        $fail('Cada URL debe ser un reel, un video o una foto pública de Facebook.');
                    }
                },
            ],
            'thumbnail' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ], [
            'facebook_links.required' => 'Pega al menos un enlace de Facebook.',
            'facebook_links.max' => 'Puedes añadir hasta 20 enlaces a la vez.',
            'facebook_links.*.url' => 'Hay una URL que no es válida.',
            'thumbnail.image' => 'La miniatura debe ser una imagen (JPG, PNG o WebP).',
            'thumbnail.max' => 'La miniatura no puede superar 5 MB.',
        ]);

        $thumbnail = null;
        if (count($urls) === 1 && $request->hasFile('thumbnail') && $request->file('thumbnail')->isValid()) {
            $directory = 'event-media/'.$event->id;
            Storage::disk('public')->makeDirectory($directory);
            $thumbnail = $request->file('thumbnail')->store($directory, 'public');
        }

        $order = $this->nextSortOrder($event);
        foreach ($urls as $index => $url) {
            $classified = EventMedia::classifyFacebookUrl($url);
            $event->media()->create([
                'kind' => $classified['kind'],
                'source' => EventMedia::SOURCE_FACEBOOK,
                'external_url' => $classified['url'],
                'thumbnail_path' => $index === 0 ? $thumbnail : null,
                'sort_order' => $order + $index,
            ]);
        }

        $count = count($urls);
        $label = $count === 1
            ? 'Enlace de Facebook añadido a la galería.'
            : $count.' enlaces de Facebook añadidos a la galería.';

        return redirect()->route('admin.events.show', $event)->with('message', $label);
    }

    private function nextSortOrder(Event $event): int
    {
        return ((int) $event->media()->max('sort_order')) + 1;
    }
}
