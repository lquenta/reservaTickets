@if(filled(config('services.google.client_id')) && filled(config('services.google.client_secret')) && filled(config('services.google.redirect')))
    <a href="{{ route('auth.google.redirect') }}"
       class="flex w-full items-center justify-center gap-3 rounded-xl border border-white/20 bg-white px-5 py-3.5 text-sm font-semibold text-slate-800 hover:bg-slate-100 transition">
        <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
            <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.4h6.5a5.6 5.6 0 0 1-2.4 3.7v3h3.9c2.3-2.1 3.5-5.2 3.5-8.8z"/>
            <path fill="#34A853" d="M12 24c3.2 0 5.9-1.1 7.9-2.9l-3.9-3c-1.1.7-2.4 1.2-4 1.2-3.1 0-5.7-2.1-6.6-4.9H1.4v3.1A12 12 0 0 0 12 24z"/>
            <path fill="#FBBC05" d="M5.4 14.4A7.2 7.2 0 0 1 5 12c0-.8.1-1.6.4-2.4V6.5H1.4A12 12 0 0 0 0 12c0 1.9.5 3.8 1.4 5.5l4-3.1z"/>
            <path fill="#EA4335" d="M12 4.8c1.7 0 3.3.6 4.5 1.8l3.4-3.4C17.9 1.1 15.2 0 12 0 7.3 0 3.2 2.7 1.4 6.5l4 3.1C6.3 6.9 8.9 4.8 12 4.8z"/>
        </svg>
        Continuar con Google
    </a>
    @if(!empty($divider))
        <div class="my-6 flex items-center gap-3 text-xs uppercase tracking-wide text-white/40">
            <span class="h-px flex-1 bg-white/15"></span>
            o
            <span class="h-px flex-1 bg-white/15"></span>
        </div>
    @endif
@endif
