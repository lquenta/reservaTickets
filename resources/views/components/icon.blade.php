@props(['name' => 'ticket'])

@php
    $paths = [
        'dashboard' => '<path d="M3 3v18h18"/><path d="M7 14v4"/><path d="M12 9v9"/><path d="M17 5v13"/>',
        'venue' => '<path d="M4 20V8l8-4 8 4v12"/><path d="M9 20v-6h6v6"/><path d="M9 10h.01M15 10h.01"/>',
        'ticket' => '<path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2M13 11v2M13 17v2"/>',
        'clipboard' => '<rect x="8" y="3" width="8" height="4" rx="1"/><path d="M8 5H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><path d="M9 12h6M9 16h4"/>',
        'refund' => '<path d="M3 10h13a5 5 0 0 1 0 10H9"/><path d="M7 6 3 10l4 4"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'chart' => '<path d="M3 17 9 11l4 4 8-8"/><path d="M14 7h7v7"/>',
        'chart-down' => '<path d="M3 7l6 6 4-4 8 8"/><path d="M14 17h7v-7"/>',
        'image' => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="1.5"/><path d="m21 15-5-5L5 19"/>',
        'document' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h4"/>',
        'pdf' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h4"/>',
        'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'home' => '<path d="M4 11 12 4l8 7"/><path d="M6 10v9h12v-9"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'bell' => '<path d="M6 9a6 6 0 0 1 12 0c0 7 3 8 3 8H3s3-1 3-8"/><path d="M10 20a2 2 0 0 0 4 0"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/>',
        'pin' => '<path d="M12 21s7-5.4 7-11a7 7 0 1 0-14 0c0 5.6 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'cart' => '<circle cx="8" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M3 4h2l2.4 12h11.2L21 8H6.5"/>',
        'star' => '<path d="m12 3 2.6 5.4L20 9.3l-4 3.9.9 5.5L12 16.4 7.1 18.7 8 13.2 4 9.3l5.4-.9z"/>',
        'seat' => '<path d="M6 11V8a3 3 0 0 1 3-3h6a3 3 0 0 1 3 3v3"/><path d="M5 11h14v4H5z"/><path d="M7 15v4M17 15v4M5 19h14"/>',
        'play' => '<circle cx="12" cy="12" r="9"/><path d="m10 8 6 4-6 4z"/>',
        'pause' => '<circle cx="12" cy="12" r="9"/><path d="M10 9v6M14 9v6"/>',
        'ban' => '<circle cx="12" cy="12" r="9"/><path d="m6.5 6.5 11 11"/>',
        'unlock' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 7.5-2"/>',
        'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'trash' => '<path d="M4 7h16"/><path d="M9 7V5h6v2"/><path d="M6 7l1 13h10l1-13"/>',
        'warning' => '<path d="M12 3 2 20h20z"/><path d="M12 9v5M12 17h.01"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/>',
        'download' => '<path d="M12 4v10"/><path d="m8 10 4 4 4-4"/><path d="M5 19h14"/>',
        'planet' => '<circle cx="12" cy="12" r="6"/><path d="M4 10c4-1 12-1 16 2M4 14c4 1 12 1 16-2"/>',
        'clapper' => '<path d="M4 8h16v12H4z"/><path d="M4 8 8 4h12l-4 4"/><path d="M8 4l4 4M14 4l4 4"/>',
        'music' => '<path d="M9 18V6l10-2v12"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="16" r="2"/>',
    ];
    $d = $paths[$name] ?? $paths['ticket'];
@endphp
<svg {{ $attributes->merge([
    'class' => 'icon-neon shrink-0',
    'fill' => 'none',
    'viewBox' => '0 0 24 24',
    'stroke-width' => '1.75',
    'stroke-linecap' => 'round',
    'stroke-linejoin' => 'round',
    'aria-hidden' => 'true',
]) }}>{!! $d !!}</svg>
