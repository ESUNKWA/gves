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

    @if (session('status'))
        <div class="mb-4 p-3 rounded-lg bg-success-soft text-success text-sm">
            {{ session('status') }}
        </div>
    @endif

    <div class="rounded-xl border border-line-soft bg-surface shadow-card overflow-hidden">
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-surface-2">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Employé
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Gabarit
                        demandé</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Motif
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Demandée
                        le</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Statut
                    </th>
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
                        <td class="px-6 py-4 text-sm text-muted">{{ $documentRequest->reason ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $documentRequest->created_at->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <x-status-chip :tone="$tones[$documentRequest->status] ?? 'neutral'">
                                {{ $statuses[$documentRequest->status] ?? $documentRequest->status }}
                            </x-status-chip>
                        </td>
                        <td class="px-6 py-4 text-right text-sm space-x-3">
                            @if ($documentRequest->status === 'pending')
                                <form method="POST"
                                    action="{{ route('documents.document-requests.approve', $documentRequest) }}"
                                    class="inline" onsubmit="return confirm('Générer ce document pour signature ?');">
                                    @csrf
                                    <button type="submit" class="text-success hover:underline">Approuver</button>
                                </form>
                                <button type="button" x-data
                                    x-on:click="$dispatch('open-modal', 'reject-doc-request-{{ $documentRequest->id }}')"
                                    class="text-danger hover:underline">Refuser</button>
                            @elseif ($documentRequest->decision_note)
                                <span class="text-muted text-xs italic">{{ $documentRequest->decision_note }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-muted">Aucune demande de document.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $documentRequests->links() }}
    </div>

    @foreach ($documentRequests as $documentRequest)
        @if ($documentRequest->status === 'pending')
            <x-modal name="reject-doc-request-{{ $documentRequest->id }}" focusable>
                <form method="POST" action="{{ route('documents.document-requests.reject', $documentRequest) }}"
                    class="p-6">
                    @csrf
                    <h2 class="text-lg font-medium text-fg">{{ __('Refuser la demande') }}</h2>
                    <p class="mt-1 text-sm text-muted">
                        {{ $documentRequest->employee->full_name }} — {{ $documentRequest->template?->name ?? '—' }}
                    </p>

                    <div class="mt-4">
                        <x-input-label for="reject_note_{{ $documentRequest->id }}" value="Motif du refus" />
                        <textarea name="decision_note" id="reject_note_{{ $documentRequest->id }}" rows="3" required
                            class="mt-1 block w-full rounded-lg border-line bg-surface text-sm text-fg shadow-sm focus:border-brand focus:ring-brand"></textarea>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <x-secondary-button type="button"
                            x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                        <x-danger-button class="ms-3">{{ __('Refuser') }}</x-danger-button>
                    </div>
                </form>
            </x-modal>
        @endif
    @endforeach
</x-app-layout>
