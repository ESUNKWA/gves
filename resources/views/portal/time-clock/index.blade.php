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
                    <form method="POST" action="{{ route('portal.time-clock.clock-in') }}" x-data="clockForm()"
                        x-on:submit.prevent="submitForm()">
                        @csrf
                        <input type="hidden" name="latitude" x-ref="latitude">
                        <input type="hidden" name="longitude" x-ref="longitude">
                        <x-primary-button type="submit" x-bind:disabled="submitting">
                            <span x-show="!submitting">{{ __("Pointer l'arrivée") }}</span>
                            <span x-show="submitting" x-cloak>{{ __('Un instant…') }}</span>
                        </x-primary-button>
                    </form>
                @elseif (!$todayEntry->clock_out)
                    <form method="POST" action="{{ route('portal.time-clock.clock-out') }}" x-data="clockForm()"
                        x-on:submit.prevent="submitForm()">
                        @csrf
                        <input type="hidden" name="latitude" x-ref="latitude">
                        <input type="hidden" name="longitude" x-ref="longitude">
                        <x-primary-button type="submit" x-bind:disabled="submitting">
                            <span x-show="!submitting">{{ __('Pointer le départ') }}</span>
                            <span x-show="submitting" x-cloak>{{ __('Un instant…') }}</span>
                        </x-primary-button>
                    </form>
                @else
                    <x-status-chip tone="success">Journée terminée</x-status-chip>
                @endif
            </div>

            @once
                <script>
                    // Best-effort geolocation before submitting a clock-in/out —
                    // never blocks the submit: denied permission, unsupported
                    // browser, or a slow GPS fix all just fall through and submit
                    // without coordinates (the IP is captured server-side either way).
                    function clockForm() {
                        return {
                            submitting: false,
                            submitForm() {
                                this.submitting = true;
                                const finish = () => this.$el.submit();

                                if (!navigator.geolocation) {
                                    finish();
                                    return;
                                }

                                const timeout = setTimeout(finish, 4000);

                                navigator.geolocation.getCurrentPosition(
                                    (position) => {
                                        clearTimeout(timeout);
                                        this.$refs.latitude.value = position.coords.latitude;
                                        this.$refs.longitude.value = position.coords.longitude;
                                        finish();
                                    },
                                    () => {
                                        clearTimeout(timeout);
                                        finish();
                                    },
                                    { timeout: 4000, maximumAge: 60000 },
                                );
                            },
                        };
                    }
                </script>
            @endonce
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
