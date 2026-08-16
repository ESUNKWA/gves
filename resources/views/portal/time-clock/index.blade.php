@php
    $formatMinutes = fn($minutes) => sprintf('%dh%02d', intdiv($minutes, 60), $minutes % 60);
@endphp

<x-app-layout>
    <x-page-header :title="__('Mon pointage')" :description="__('Pointez votre arrivée et votre départ, et suivez vos heures.')" />

    <x-portal-tabs />

    <div class="rounded-xl border border-line-soft bg-surface shadow-card p-6 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm text-muted">Aujourd'hui — {{ now()->translatedFormat('l d F Y') }}</p>
                <p class="mt-1 text-lg font-semibold text-fg">
                    @if ($todayEntry?->clock_in)
                        Arrivée : {{ $todayEntry->clock_in->format('H:i') }}
                        @if ($todayEntry->clock_out)
                            &middot; Départ : {{ $todayEntry->clock_out->format('H:i') }}
                        @endif
                    @else
                        Vous n'avez pas encore pointé aujourd'hui.
                    @endif
                </p>
            </div>

            <div class="flex gap-3">
                @if (!$todayEntry?->clock_in)
                    <form method="POST" action="{{ route('portal.time-clock.clock-in') }}">
                        @csrf
                        <x-primary-button type="submit">{{ __("Pointer l'arrivée") }}</x-primary-button>
                    </form>
                @elseif (!$todayEntry->clock_out)
                    <form method="POST" action="{{ route('portal.time-clock.clock-out') }}">
                        @csrf
                        <x-primary-button type="submit">{{ __('Pointer le départ') }}</x-primary-button>
                    </form>
                @else
                    <x-status-chip tone="success">Journée terminée</x-status-chip>
                @endif
            </div>
        </div>
    </div>

    @unless ($schedule->hasAnyDayDefined())
        <div class="mb-6 p-3 rounded-lg bg-warning-soft text-warning text-sm">
            Aucun horaire de travail ne vous a été assigné — les retards et heures supplémentaires ne peuvent pas être
            calculés pour l'instant.
        </div>
    @endunless

    <div class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-3">
        <x-stat-card label="Heures travaillées (ce mois)" :value="$formatMinutes($monthWorkedMinutes)" />
        <x-stat-card label="Retard cumulé (ce mois)" :value="$formatMinutes($monthLateMinutes)" />
        <x-stat-card label="Heures supplémentaires (ce mois)" :value="$formatMinutes($monthOvertimeMinutes)" />
    </div>

    <x-data-table>
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-surface-2">
                <tr>
                    <th data-sort class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Date</th>
                    <th data-sort class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Arrivée</th>
                    <th data-sort class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Départ</th>
                    <th data-sort class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Travaillé</th>
                    <th data-sort class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Retard</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($recentEntries as $entry)
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium text-fg"
                            data-sort-value="{{ $entry->date->timestamp }}">
                            {{ $entry->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm text-muted">{{ $entry->clock_in?->format('H:i') ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-muted">{{ $entry->clock_out?->format('H:i') ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-muted">
                            {{ $entry->workedMinutes() !== null ? $formatMinutes($entry->workedMinutes()) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @php $late = $entry->lateMinutes($schedule); @endphp
                            @if ($late > 0)
                                <x-status-chip tone="warning">{{ $formatMinutes($late) }}</x-status-chip>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr data-empty-row>
                        <td colspan="5" class="px-4 py-6 text-center text-sm text-muted">Aucun pointage sur les 14
                            derniers jours.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-data-table>
</x-app-layout>
