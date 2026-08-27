@guest
<div class="rounded-2xl border border-purple-900/50 bg-black/40 p-5 sm:p-6 space-y-4">
    <h2 class="font-display text-lg font-bold text-[#ff2daa] tracking-wider">Tus datos</h2>
    <p class="text-white/60 text-sm">Los usamos para enviarte los tickets. No se crea una cuenta.</p>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="guest_first_name" class="block text-sm font-medium text-white/80 mb-1">Nombre</label>
            <input id="guest_first_name" type="text" name="guest_first_name" value="{{ old('guest_first_name') }}" required maxlength="255" autocomplete="given-name"
                   class="w-full rounded-xl border border-purple-900/50 bg-black/60 px-4 py-3 text-white placeholder-white/40 focus:ring-2 focus:ring-[#39ff14] @error('guest_first_name') border-red-500 @enderror">
            @error('guest_first_name')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="guest_last_name" class="block text-sm font-medium text-white/80 mb-1">Apellido</label>
            <input id="guest_last_name" type="text" name="guest_last_name" value="{{ old('guest_last_name') }}" required maxlength="255" autocomplete="family-name"
                   class="w-full rounded-xl border border-purple-900/50 bg-black/60 px-4 py-3 text-white placeholder-white/40 focus:ring-2 focus:ring-[#39ff14] @error('guest_last_name') border-red-500 @enderror">
            @error('guest_last_name')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label for="guest_phone" class="block text-sm font-medium text-white/80 mb-1">Teléfono celular</label>
        <input id="guest_phone" type="text" name="guest_phone" value="{{ old('guest_phone') }}" required maxlength="20" autocomplete="tel"
               class="w-full rounded-xl border border-purple-900/50 bg-black/60 px-4 py-3 text-white placeholder-white/40 focus:ring-2 focus:ring-[#39ff14] @error('guest_phone') border-red-500 @enderror">
        @error('guest_phone')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="guest_email" class="block text-sm font-medium text-white/80 mb-1">Correo electrónico</label>
        <input id="guest_email" type="email" name="guest_email" value="{{ old('guest_email') }}" required maxlength="255" autocomplete="email"
               class="w-full rounded-xl border border-purple-900/50 bg-black/60 px-4 py-3 text-white placeholder-white/40 focus:ring-2 focus:ring-[#39ff14] @error('guest_email') border-red-500 @enderror">
        @error('guest_email')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="guest_email_confirmation" class="block text-sm font-medium text-white/80 mb-1">Confirmar correo</label>
        <input id="guest_email_confirmation" type="email" name="guest_email_confirmation" value="{{ old('guest_email_confirmation') }}" required maxlength="255" autocomplete="email"
               class="w-full rounded-xl border border-purple-900/50 bg-black/60 px-4 py-3 text-white placeholder-white/40 focus:ring-2 focus:ring-[#39ff14] @error('guest_email_confirmation') border-red-500 @enderror">
        @error('guest_email_confirmation')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
    </div>
</div>
@endguest
