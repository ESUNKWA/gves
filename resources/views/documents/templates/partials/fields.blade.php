@php
    $val = fn($field, $default = null) => $showErrors ? old($field, $default) : $default;
    $err = fn($field) => $showErrors ? $errors->get($field) : [];
@endphp

<div class="mt-6 grid grid-cols-1 gap-4">
    <div>
        <x-input-label for="name_{{ $id }}" value="Nom du gabarit" />
        <x-text-input name="name" id="name_{{ $id }}" value="{{ $val('name', $documentTemplate?->name) }}"
            class="mt-1" />
        <x-input-error :messages="$err('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="category_{{ $id }}" value="Catégorie" />
        <x-select name="category" id="category_{{ $id }}" class="mt-1">
            @foreach ($categories as $value => $label)
                <option value="{{ $value }}" @selected($val('category', $documentTemplate?->category) === $value)>
                    {{ $label }}</option>
            @endforeach
        </x-select>
        <x-input-error :messages="$err('category')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="content_{{ $id }}" value="Contenu" />
        <textarea name="content" id="content_{{ $id }}" rows="10"
            class="mt-1 block w-full rounded-lg border-line bg-surface text-sm text-fg shadow-sm focus:border-brand focus:ring-brand font-mono">{{ $val('content', $documentTemplate?->content) }}</textarea>
        <x-input-error :messages="$err('content')" class="mt-2" />

        <div class="mt-2 rounded-lg bg-surface-2 p-3">
            <p class="text-xs font-medium text-fg mb-1.5">Variables disponibles (cliquer pour copier) :</p>
            <div class="flex flex-wrap gap-1.5">
                @foreach ($variables as $tag => $label)
                    <button type="button" title="{{ $label }}" x-data
                        x-on:click="navigator.clipboard.writeText('{{ $tag }}')"
                        class="rounded bg-surface px-1.5 py-0.5 text-xs font-mono text-brand border border-line-soft hover:bg-brand/10">{{ $tag }}</button>
                @endforeach
            </div>
        </div>
    </div>

    <div class="flex items-center">
        <input type="hidden" name="is_active" value="0">
        <input name="is_active" id="is_active_{{ $id }}" type="checkbox" value="1"
            @checked($val('is_active', $documentTemplate?->is_active ?? true)) class="rounded border-line text-brand shadow-sm focus:ring-brand">
        <label for="is_active_{{ $id }}" class="ml-2 text-sm text-muted">Actif</label>
    </div>
</div>
