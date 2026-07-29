<x-app-layout>
    <x-page-header :title="__('Demandes de congé')" :description="$isHr ? __('Toutes les demandes de l\'organisation.') : __('Les demandes de votre équipe.')" />

    <form method="GET" action="{{ route('leaves.requests.index') }}" class="mb-6 max-w-xs">
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
                        Type</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Période</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Jours</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Statut</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($leaveRequests as $leaveRequest)
                    @php
                        $tones = [
                            'pending' => 'warning',
                            'approved' => 'success',
                            'rejected' => 'danger',
                            'cancelled' => 'neutral',
                        ];
                    @endphp
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-fg">
                            <a href="{{ route('organisation.employees.show', $leaveRequest->employee) }}"
                                class="hover:underline">{{ $leaveRequest->employee->full_name }}</a>
                        </td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $leaveRequest->leaveType->name }}</td>
                        <td class="px-6 py-4 text-sm text-muted"
                            data-sort-value="{{ $leaveRequest->start_date->timestamp }}">
                            {{ $leaveRequest->start_date->format('d/m/Y') }} —
                            {{ $leaveRequest->end_date->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-muted">
                            {{ rtrim(rtrim(number_format($leaveRequest->days_count, 2), '0'), '.') }}</td>
                        <td class="px-6 py-4 text-sm">
                            <x-status-chip :tone="$tones[$leaveRequest->status] ?? 'neutral'">
                                {{ $statuses[$leaveRequest->status] ?? $leaveRequest->status }}
                            </x-status-chip>
                        </td>
                        <td class="px-6 py-4 text-right text-sm space-x-3">
                            @if ($leaveRequest->status === 'pending')
                                <form method="POST" action="{{ route('leaves.requests.approve', $leaveRequest) }}"
                                    class="inline" data-confirm="Approuver cette demande ?">
                                    @csrf
                                    <button type="submit" class="text-success hover:underline">Approuver</button>
                                </form>
                                <button type="button" x-data
                                    x-on:click="$dispatch('open-modal', 'reject-{{ $leaveRequest->id }}')"
                                    class="text-danger hover:underline">Refuser</button>
                            @elseif ($leaveRequest->decision_note)
                                <span class="text-muted text-xs italic">{{ $leaveRequest->decision_note }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr data-empty-row>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-muted">Aucune demande de congé.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-data-table>

    @foreach ($leaveRequests as $leaveRequest)
        @if ($leaveRequest->status === 'pending')
            <x-modal name="reject-{{ $leaveRequest->id }}" focusable>
                <form method="POST" action="{{ route('leaves.requests.reject', $leaveRequest) }}" class="p-6">
                    @csrf
                    <h2 class="text-lg font-medium text-fg">{{ __('Refuser la demande') }}</h2>
                    <p class="mt-1 text-sm text-muted">
                        {{ $leaveRequest->employee->full_name }} — {{ $leaveRequest->leaveType->name }}
                        ({{ $leaveRequest->start_date->format('d/m/Y') }} —
                        {{ $leaveRequest->end_date->format('d/m/Y') }})
                    </p>

                    <div class="mt-4">
                        <x-input-label for="decision_note_{{ $leaveRequest->id }}" value="Motif du refus" />
                        <textarea name="decision_note" id="decision_note_{{ $leaveRequest->id }}" rows="3" required
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
