@php
    $val = fn($field, $default = null) => $showErrors ? old($field, $default) : $default;
    $err = fn($field) => $showErrors ? $errors->get($field) : [];
@endphp

<div class="mt-6 grid grid-cols-1 gap-4">
    <div>
        <x-input-label for="name_{{ $id }}" value="Nom du pays" />
        <x-text-input name="name" id="name_{{ $id }}" value="{{ $val('name', $country?->name) }}"
            class="mt-1" />
        <x-input-error :messages="$err('name')" class="mt-2" />
    </div>

    <div class="flex items-center">
        <input type="hidden" name="is_active" value="0">
        <input name="is_active" id="is_active_{{ $id }}" type="checkbox" value="1"
            @checked($val('is_active', $country?->is_active ?? true)) class="rounded border-line text-brand shadow-sm focus:ring-brand">
        <label for="is_active_{{ $id }}" class="ml-2 text-sm text-muted">Actif (visible dans les listes
            déroulantes)</label>
    </div>
</div>
