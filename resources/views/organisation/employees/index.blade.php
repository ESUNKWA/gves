<x-app-layout>
    <x-page-header :title="__('Employés')" :description="__('Consultez et gérez les dossiers de vos employés.')">
        <x-slot:actions>
            @can('employees.manage')
                <a href="{{ route('organisation.employees.create') }}">
                    <x-primary-button type="button">
                        {{ __('Nouvel employé') }}
                    </x-primary-button>
                </a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form method="GET" action="{{ route('organisation.employees.index') }}" class="mb-6 flex flex-wrap gap-3">
        <x-select name="status" class="w-auto" onchange="this.form.submit()">
            <option value="">Tous les statuts</option>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
            @endforeach
        </x-select>

        <x-select name="site_id" class="w-auto" onchange="this.form.submit()">
            <option value="">Tous les sites</option>
            @foreach ($sites as $site)
                <option value="{{ $site->id }}" @selected($site_id === $site->id)>{{ $site->name }}</option>
            @endforeach
        </x-select>

        <x-select name="department_id" class="w-auto" onchange="this.form.submit()">
            <option value="">Tous les départements</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected($department_id === $department->id)>{{ $department->name }}</option>
            @endforeach
        </x-select>

        <x-secondary-button type="submit">{{ __('Filtrer') }}</x-secondary-button>
    </form>

    <x-data-table>
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-surface-2">
                <tr>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Employé</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Matricule</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Poste</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Département</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Site</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Statut</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($employees as $employee)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-fg">
                            <a href="{{ route('organisation.employees.show', $employee) }}" class="hover:underline">
                                {{ $employee->full_name }}
                            </a>
                        </td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $employee->employee_number }}</td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $employee->position?->title ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $employee->department?->name ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $employee->site?->name ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm">
                            @php
                                $statusTones = [
                                    'active' => 'success',
                                    'on_leave' => 'warning',
                                    'suspended' => 'danger',
                                    'terminated' => 'neutral',
                                ];
                            @endphp
                            <x-status-chip :tone="$statusTones[$employee->status] ?? 'neutral'">
                                {{ $statuses[$employee->status] ?? $employee->status }}
                            </x-status-chip>
                        </td>
                        <td class="px-6 py-4 text-right text-sm space-x-3">
                            <a href="{{ route('organisation.employees.show', $employee) }}"
                                class="text-brand hover:underline">Voir</a>
                            @can('employees.manage')
                                <form method="POST" action="{{ route('organisation.employees.destroy', $employee) }}"
                                    class="inline" data-confirm="Supprimer cet employé ?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-danger hover:underline">Supprimer</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr data-empty-row>
                        <td colspan="7" class="px-6 py-8 text-center text-sm text-muted">Aucun employé pour le
                            moment.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-data-table>
</x-app-layout>
