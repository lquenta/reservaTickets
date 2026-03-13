@extends('layouts.admin')

@section('title', 'Integrantes - Admin')

@section('admin')
<div class="mb-8 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold text-slate-800 dark:text-white">Integrantes del equipo</h1>
        <p class="text-slate-600 dark:text-slate-400 mt-1">Fotos que se muestran en el carrusel de la sección «Quiénes somos» de la portada.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.team-members.create') }}" class="rounded-xl bg-[#e50914] hover:bg-red-600 px-5 py-2.5 text-white font-semibold transition">
            + Añadir uno
        </a>
        <a href="{{ route('admin.team-members.bulk-create') }}" class="rounded-xl border border-violet-500/60 text-violet-600 dark:text-violet-400 hover:bg-violet-50 dark:hover:bg-violet-900/20 px-5 py-2.5 font-medium transition">
            Subir varios
        </a>
    </div>
</div>

<div class="rounded-2xl border-2 border-violet-200/60 dark:border-violet-700/50 bg-white dark:bg-slate-800/80 overflow-hidden shadow-lg">
    <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50">
        <h2 class="text-lg font-semibold text-slate-800 dark:text-white">Listado (orden de aparición)</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Arrastra las filas para cambiar el orden.</p>
    </div>
    <div class="p-6" id="team-members-sortable">
        @forelse($members as $member)
            <div class="flex items-center gap-4 py-4 border-b border-slate-200 dark:border-slate-700 last:border-0 bg-white dark:bg-slate-800/80 rounded-lg px-3 sortable-item" data-member-id="{{ $member->id }}">
                <div class="sortable-handle shrink-0 text-slate-400 dark:text-slate-500 cursor-grab active:cursor-grabbing" aria-hidden="true" title="Arrastrar para reordenar">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 6h2v2H8V6zm0 5h2v2H8v-2zm0 5h2v2H8v-2zm5-10h2v2h-2V6zm0 5h2v2h-2v-2zm0 5h2v2h-2v-2zm5-10h2v2h-2V6zm0 5h2v2h-2v-2zm0 5h2v2h-2v-2z"/></svg>
                </div>
                <div class="w-20 h-20 rounded-xl overflow-hidden bg-slate-200 dark:bg-slate-700 shrink-0 ring-2 ring-slate-300 dark:ring-slate-600">
                    <img src="{{ asset('storage/'.$member->photo_path) }}" alt="{{ $member->name ?: 'Integrante' }}" class="w-full h-full object-cover" draggable="false">
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-slate-800 dark:text-white">{{ $member->name ?: '—' }}</p>
                    @if($member->role)
                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ $member->role }}</p>
                    @endif
                    <p class="text-xs text-slate-500 dark:text-slate-500 mt-0.5">Orden {{ $member->sort_order }}</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('admin.team-members.edit', $member) }}" class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 transition">Editar</a>
                    <form action="{{ route('admin.team-members.destroy', $member) }}" method="POST" onsubmit="return confirm('¿Eliminar este integrante?');" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-lg px-3 py-1.5 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 transition">Eliminar</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="py-12 text-center text-slate-500 dark:text-slate-400">No hay integrantes. <a href="{{ route('admin.team-members.create') }}" class="text-[#e50914] hover:underline">Añade el primero</a> para que aparezcan en la sección Quiénes somos.</p>
        @endforelse
    </div>
</div>

<form id="team-members-reorder-form" action="{{ route('admin.team-members.reorder') }}" method="POST" class="hidden">
    @csrf
    @method('PATCH')
</form>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var el = document.getElementById('team-members-sortable');
    if (!el || !el.querySelector('.sortable-item')) return;

    var form = document.getElementById('team-members-reorder-form');
    if (!form) return;

    new Sortable(el, {
        animation: 150,
        handle: '.sortable-handle',
        ghostClass: 'opacity-50',
        onEnd: function() {
            var items = el.querySelectorAll('.sortable-item');
            var order = Array.from(items).map(function(item) { return item.getAttribute('data-member-id'); });
            form.querySelectorAll('input[name="order[]"]').forEach(function(input) { input.remove(); });
            order.forEach(function(id) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'order[]';
                input.value = id;
                form.appendChild(input);
            });
            form.submit();
        }
    });
});
</script>
@endpush
@endsection
