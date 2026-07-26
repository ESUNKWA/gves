<x-app-layout>
    <x-page-header :title="__('Suivi des présences')" :description="$isHr ? __('Présences de toute l\'organisation.') : __('Présences de votre équipe.')" />

    <form method="GET" action="{{ route('attendance.requests.index') }}" class="mb-6 max-w-xs">
        <x-text-input type="date" name="date" value="{{ $date }}" class="w-full"
            onchange="this.form.submit()" />
    </form>

    <div class="rounded-xl border border-line-soft bg-surface shadow-card overflow-hidden">
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-surface-2">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Employé
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Arrivée
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Départ
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Statut
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($employees as $employee)
                    @php
                        $entry = $employee->timeEntries->first();
                        $late = $entry?->lateMinutes($employee->workSchedule) ?? 0;
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
                                    ({{ intdiv($late, 60) }}h{{ str_pad($late % 60, 2, '0', STR_PAD_LEFT) }})</x-status-chip>
                            @elseif ($entry->clock_out)
                                <x-status-chip tone="success">Journée terminée</x-status-chip>
                            @else
                                <x-status-chip tone="success">Présent</x-status-chip>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-sm text-muted">Aucun employé à afficher.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
