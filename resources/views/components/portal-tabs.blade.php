@php
    $tabs = [
        ['route' => 'portal.profile.edit', 'active' => 'portal.profile.*', 'label' => 'Mon profil'],
        ['route' => 'portal.time-clock.index', 'active' => 'portal.time-clock.*', 'label' => 'Mon pointage'],
        ['route' => 'portal.leaves.index', 'active' => 'portal.leaves.*', 'label' => 'Mes congés'],
        ['route' => 'portal.documents.index', 'active' => 'portal.documents.*', 'label' => 'Mes documents'],
        ['route' => 'portal.payslips.index', 'active' => 'portal.payslips.*', 'label' => 'Ma paie'],
    ];
@endphp

<div class="mb-6 border-b border-line">
    <nav class="-mb-px flex flex-wrap gap-x-6">
        @foreach ($tabs as $tab)
            <a href="{{ route($tab['route']) }}"
                class="border-b-2 py-3 text-sm font-medium {{ request()->routeIs($tab['active']) ? 'border-brand text-brand' : 'border-transparent text-muted hover:border-line hover:text-fg' }}">
                {{ __($tab['label']) }}
            </a>
        @endforeach
    </nav>
</div>
