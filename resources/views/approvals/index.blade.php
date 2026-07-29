<x-app-layout>
    <x-page-header :title="__('Validations en attente')" :description="__('Demandes de documents qui attendent votre décision.')" />

    <x-data-table>
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-surface-2">
                <tr>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Étape</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Employé</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Document</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Demandé le</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($approvals as $approval)
                    <tr>
                        <td class="px-6 py-4 text-sm">
                            <x-status-chip tone="warning">{{ $approval->label() }}</x-status-chip>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-fg">
                            {{ $approval->generatedDocument->employee->full_name }}
                        </td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $approval->generatedDocument->title }}</td>
                        <td class="px-6 py-4 text-sm text-muted"
                            data-sort-value="{{ $approval->generatedDocument->created_at->timestamp }}">
                            {{ $approval->generatedDocument->created_at->format('d/m/Y à H:i') }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            <a href="{{ route('approvals.show', $approval) }}"
                                class="text-brand hover:underline">Consulter</a>
                        </td>
                    </tr>
                @empty
                    <tr data-empty-row>
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-muted">Aucune validation en
                            attente.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-data-table>
</x-app-layout>
