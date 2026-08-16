@php
    $isEditingSchedule = old('_modal') === 'time-entry-schedule';
    $isAddingEntry = old('_modal') === 'time-entry-add';
    $formatMinutes = fn($minutes) => sprintf('%dh%02d', intdiv($minutes, 60), $minutes % 60);
@endphp

<div class="flex items-center justify-between mb-4">
    <div>
        <h3 class="text-base font-semibold text-fg">Horaire de travail</h3>
        @unless ($hasOwnWorkSchedule)
            <p class="text-xs text-muted">Horaire par défaut de l'entreprise — pas de personnalisation pour cet
                employé.</p>
        @endunless
    </div>
    @can('employees.manage')
        <x-secondary-button type="button" x-data
            x-on:click="$dispatch('open-modal', 'time-entry-schedule')">{{ __("Modifier l'horaire") }}</x-secondary-button>
    @endcan
</div>

<div class="rounded-xl border border-line-soft bg-surface shadow-card overflow-x-auto mb-8">
    <table class="min-w-full divide-y divide-line">
        <thead class="bg-surface-2">
            <tr>
                @foreach ($dayLabels as $label)
                    <th class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        {{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                @foreach ($dayLabels as $day => $label)
                    @php
                        $start = $workSchedule->{"{$day}_start"};
                        $end = $workSchedule->{"{$day}_end"};
                    @endphp
                    <td class="px-4 py-3 text-sm text-muted">
                        @if ($start && $end)
                            {{ \Illuminate\Support\Carbon::parse($start)->format('H:i') }} –
                            {{ \Illuminate\Support\Carbon::parse($end)->format('H:i') }}
                        @else
                            Repos
                        @endif
                    </td>
                @endforeach
            </tr>
        </tbody>
    </table>
</div>

<div class="flex items-center justify-between mb-4">
    <h3 class="text-base font-semibold text-fg">Historique de pointage</h3>
    @can('employees.manage')
        <x-secondary-button type="button" x-data
            x-on:click="$dispatch('open-modal', 'time-entry-add')">{{ __('Ajouter / corriger un pointage') }}</x-secondary-button>
    @endcan
</div>

<x-data-table>
    <table class="min-w-full divide-y divide-line">
        <thead class="bg-surface-2">
            <tr>
                <th data-sort class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">Date
                </th>
                <th data-sort class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                    Arrivée</th>
                <th data-sort class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                    Départ</th>
                <th data-sort class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                    Travaillé</th>
                <th data-sort class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                    Retard</th>
                <th data-sort class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                    Source</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-line">
            @forelse ($timeEntries as $entry)
                @php $late = $entry->lateMinutes($employee->effectiveWorkSchedule()); @endphp
                <tr>
                    <td class="px-4 py-3 text-sm font-medium text-fg" data-sort-value="{{ $entry->date->timestamp }}">
                        {{ $entry->date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-sm text-muted">{{ $entry->clock_in?->format('H:i') ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-muted">{{ $entry->clock_out?->format('H:i') ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-muted">
                        {{ $entry->workedMinutes() !== null ? $formatMinutes($entry->workedMinutes()) : '—' }}</td>
                    <td class="px-4 py-3 text-sm">
                        @if ($late > 0)
                            <x-status-chip tone="warning">{{ $formatMinutes($late) }}</x-status-chip>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-muted">
                        {{ $entry->source === 'manual' ? 'Corrigé' : 'Auto-pointage' }}</td>
                    <td class="px-4 py-3 text-right text-sm">
                        @can('employees.manage')
                            <form method="POST"
                                action="{{ route('organisation.employees.time-entries.destroy', [$employee, $entry]) }}"
                                class="inline" data-confirm="Supprimer ce pointage ?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-danger hover:underline">Supprimer</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr data-empty-row>
                    <td colspan="7" class="px-4 py-6 text-center text-sm text-muted">Aucun pointage enregistré.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</x-data-table>

@can('employees.manage')
    <x-modal name="time-entry-schedule" :show="$isEditingSchedule" focusable maxWidth="4xl">
        <form method="POST" action="{{ route('organisation.employees.work-schedule.update', $employee) }}" class="p-6">
            @csrf
            @method('PUT')
            <input type="hidden" name="_modal" value="time-entry-schedule">

            <h2 class="text-lg font-medium text-fg">{{ __('Horaire de travail') }}</h2>
            <p class="mt-1 text-sm text-muted">Laissez les deux champs vides pour un jour non travaillé.</p>

            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                @include('attendance.partials.schedule-fields', [
                    'schedule' => $workSchedule,
                    'showErrors' => $isEditingSchedule,
                ])
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                <x-primary-button class="ms-3">{{ __('Enregistrer') }}</x-primary-button>
            </div>
        </form>
    </x-modal>

    <x-modal name="time-entry-add" :show="$isAddingEntry" focusable>
        <form method="POST" action="{{ route('organisation.employees.time-entries.store', $employee) }}" class="p-6">
            @csrf
            <input type="hidden" name="_modal" value="time-entry-add">

            <h2 class="text-lg font-medium text-fg">{{ __('Ajouter / corriger un pointage') }}</h2>

            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <x-input-label for="time_entry_date" value="Date" />
                    <x-text-input name="date" id="time_entry_date" type="date" value="{{ old('date') }}"
                        class="mt-1" />
                    <x-input-error :messages="$isAddingEntry ? $errors->get('date') : []" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="time_entry_clock_in" value="Arrivée" />
                    <x-text-input name="clock_in" id="time_entry_clock_in" type="time" value="{{ old('clock_in') }}"
                        class="mt-1" />
                    <x-input-error :messages="$isAddingEntry ? $errors->get('clock_in') : []" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="time_entry_clock_out" value="Départ" />
                    <x-text-input name="clock_out" id="time_entry_clock_out" type="time" value="{{ old('clock_out') }}"
                        class="mt-1" />
                    <x-input-error :messages="$isAddingEntry ? $errors->get('clock_out') : []" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="time_entry_note" value="Note (optionnel)" />
                    <textarea name="note" id="time_entry_note" rows="2"
                        class="mt-1 block w-full rounded-lg border-line bg-surface text-sm text-fg shadow-sm focus:border-brand focus:ring-brand">{{ old('note') }}</textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button"
                    x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                <x-primary-button class="ms-3">{{ __('Enregistrer') }}</x-primary-button>
            </div>
        </form>
    </x-modal>
@endcan
