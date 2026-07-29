@props(['perPage' => 10])

<div x-data="dataTable({ perPage: {{ (int) $perPage }} })">
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-t-xl border border-b-0 border-line-soft bg-surface-2 px-4 py-2.5"
        x-show="totalRows > 0" style="display: none;">
        <div class="relative w-full max-w-xs">
            <svg class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-faint" fill="none"
                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
            <input type="search" x-model="query" placeholder="{{ __('Rechercher…') }}"
                class="block w-full rounded-lg border-line bg-surface py-1.5 pl-8 text-sm text-fg shadow-sm placeholder:text-faint focus:border-brand focus:ring-brand">
        </div>

        <div class="flex items-center gap-1.5 text-xs text-muted">
            {{ __('Afficher') }}
            <select x-model.number="perPage"
                class="rounded-lg border-line bg-surface py-1 text-xs text-fg shadow-sm focus:border-brand focus:ring-brand">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            {{ __('lignes') }}
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-line-soft bg-surface shadow-card"
        :class="totalRows > 0 && 'rounded-t-none'" x-ref="wrapper">
        {{ $slot }}
    </div>

    <p class="mt-2 px-1 text-sm text-muted" x-show="totalRows > 0 && visibleCount === 0" style="display: none;">
        {{ __('Aucun résultat pour') }} « <span x-text="query"></span> ».
    </p>

    <div class="mt-3 flex flex-wrap items-center justify-between gap-3 px-1" x-show="totalPages > 1"
        style="display: none;">
        <p class="text-xs text-muted">
            <span x-text="pageStart"></span>–<span x-text="pageEnd"></span> {{ __('sur') }}
            <span x-text="visibleCount"></span>
        </p>

        <div class="flex items-center gap-1">
            <button type="button" x-on:click="prevPage()" :disabled="page === 1"
                class="rounded-lg border border-line px-2.5 py-1 text-xs text-fg hover:bg-surface-2 disabled:cursor-not-allowed disabled:opacity-40">
                {{ __('Précédent') }}
            </button>
            <span class="px-2 text-xs text-muted">
                <span x-text="page"></span> / <span x-text="totalPages"></span>
            </span>
            <button type="button" x-on:click="nextPage()" :disabled="page === totalPages"
                class="rounded-lg border border-line px-2.5 py-1 text-xs text-fg hover:bg-surface-2 disabled:cursor-not-allowed disabled:opacity-40">
                {{ __('Suivant') }}
            </button>
        </div>
    </div>
</div>
