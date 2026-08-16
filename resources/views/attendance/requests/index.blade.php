<x-app-layout>
    <x-page-header :title="__('Suivi des présences')" :description="$isHr ? __('Présences de toute l\'organisation.') : __('Présences de votre équipe.')" />

    @if ($isHr)
        <div class="mb-6 rounded-xl border border-line-soft bg-surface p-4 shadow-card">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-fg">{{ __('Horaire par défaut') }}</p>
                    <p class="text-xs text-muted">Appliqué à tout employé sans horaire personnalisé — modifiable
                        individuellement depuis sa fiche.</p>
                </div>
                <x-secondary-button type="button" x-data
                    x-on:click="$dispatch('open-modal', 'default-work-schedule')">{{ __("Modifier l'horaire") }}</x-secondary-button>
            </div>
        </div>

        <x-modal name="default-work-schedule" :show="old('_modal') === 'default-work-schedule'" focusable maxWidth="4xl">
            <form method="POST" action="{{ route('attendance.work-schedule.update') }}" class="p-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="_modal" value="default-work-schedule">

                <h2 class="text-lg font-medium text-fg">{{ __('Horaire par défaut') }}</h2>
                <p class="mt-1 text-sm text-muted">Laissez les deux champs vides pour un jour non travaillé.</p>

                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @include('attendance.partials.schedule-fields', [
                        'schedule' => $defaultSchedule,
                        'showErrors' => old('_modal') === 'default-work-schedule',
                    ])
                </div>

                <div class="mt-6 flex justify-end">
                    <x-secondary-button type="button"
                        x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                    <x-primary-button class="ms-3">{{ __('Enregistrer') }}</x-primary-button>
                </div>
            </form>
        </x-modal>
    @endif

    <form method="GET" action="{{ route('attendance.requests.index') }}" class="mb-6 max-w-xs">
        <x-text-input type="date" name="date" value="{{ $date }}" class="w-full"
            onchange="this.form.submit()" />
    </form>

    <x-data-table>
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-surface-2">
                <tr>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Employé</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Arrivée</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Départ</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Statut</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($employees as $employee)
                    @php
                        $entry = $employee->timeEntries->first();
                        $late = $entry?->lateMinutes($employee->effectiveWorkSchedule()) ?? 0;
                    @endphp
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-fg">
                            <a href="{{ route('organisation.employees.show', $employee) }}"
                                class="hover:underline">{{ $employee->full_name }}</a>
                        </td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $entry?->clock_in?->format('H:i') ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $entry?->clock_out?->format('H:i') ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm">
                            @if (!$entry?->clock_in)
                                <x-status-chip tone="neutral">Absent</x-status-chip>
                            @elseif ($late > 0)
                                <x-status-chip tone="warning">En retard
                                    ({{ intdiv($late, 60) }}h{{ str_pad($late % 60, 2, '0', STR_PAD_LEFT) }})
                                </x-status-chip>
                            @elseif ($entry->clock_out)
                                <x-status-chip tone="success">Journée terminée</x-status-chip>
                            @else
                                <x-status-chip tone="success">Présent</x-status-chip>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr data-empty-row>
                        <td colspan="4" class="px-6 py-8 text-center text-sm text-muted">Aucun employé à afficher.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-data-table>
</x-app-layout>
