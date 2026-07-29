<x-app-layout>
    <x-page-header :title="__('Suivi des signatures')" :description="__('Tous les documents générés pour signature, tous employés confondus.')" />

    <form method="GET" action="{{ route('documents.requests.index') }}" class="mb-6 max-w-xs">
        <x-select name="status" onchange="this.form.submit()">
            <option value="">Tous les statuts</option>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
            @endforeach
        </x-select>
    </form>

    <x-data-table>
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-surface-2">
                <tr>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Employé</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Document</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Généré le</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Signé le</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Statut</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($generatedDocuments as $generatedDocument)
                    @php
                        $tones = ['pending' => 'warning', 'signed' => 'success', 'cancelled' => 'neutral'];
                    @endphp
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-fg">
                            <a href="{{ route('organisation.employees.show', $generatedDocument->employee) }}"
                                class="hover:underline">{{ $generatedDocument->employee->full_name }}</a>
                        </td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $generatedDocument->title }}</td>
                        <td class="px-6 py-4 text-sm text-muted"
                            data-sort-value="{{ $generatedDocument->created_at->timestamp }}">
                            {{ $generatedDocument->created_at->format('d/m/Y à H:i') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-muted"
                            data-sort-value="{{ $generatedDocument->signed_at?->timestamp ?? 0 }}">
                            {{ $generatedDocument->signed_at?->format('d/m/Y à H:i') ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm">
                            <x-status-chip :tone="$tones[$generatedDocument->status] ?? 'neutral'">
                                {{ $statuses[$generatedDocument->status] ?? $generatedDocument->status }}
                            </x-status-chip>
                        </td>
                    </tr>
                @empty
                    <tr data-empty-row>
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-muted">Aucun document généré pour
                            le moment.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-data-table>
</x-app-layout>
