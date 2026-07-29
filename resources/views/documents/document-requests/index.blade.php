<x-app-layout>
    <x-page-header :title="__('Demandes de documents')" :description="__('Documents demandés par les employés en libre-service.')" />

    <form method="GET" action="{{ route('documents.document-requests.index') }}" class="mb-6 max-w-xs">
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
                        Gabarit demandé</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Demandée le</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Statut</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($documentRequests as $documentRequest)
                    @php
                        $tones = [
                            'pending' => 'warning',
                            'fulfilled' => 'success',
                            'rejected' => 'danger',
                            'cancelled' => 'neutral',
                        ];
                    @endphp
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-fg">
                            <a href="{{ route('organisation.employees.show', $documentRequest->employee) }}"
                                class="hover:underline">{{ $documentRequest->employee->full_name }}</a>
                        </td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $documentRequest->template?->name ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-muted"
                            data-sort-value="{{ $documentRequest->created_at->timestamp }}">
                            {{ $documentRequest->created_at->format('d/m/Y à H:i') }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <x-status-chip :tone="$tones[$documentRequest->status] ?? 'neutral'">
                                {{ $statuses[$documentRequest->status] ?? $documentRequest->status }}
                            </x-status-chip>
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            <a href="{{ route('documents.document-requests.show', $documentRequest) }}"
                                class="text-brand hover:underline">
                                {{ $documentRequest->status === 'pending' ? 'Consulter' : 'Voir le détail' }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr data-empty-row>
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-muted">Aucune demande de document.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-data-table>
</x-app-layout>
