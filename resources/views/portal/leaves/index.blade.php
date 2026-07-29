<x-app-layout>
    <x-page-header :title="__('Mes congés')" :description="__('Votre solde et l\'historique de vos demandes.')">
        <x-slot:actions>
            <x-primary-button type="button" x-data
                x-on:click="$dispatch('open-modal', 'my-leave-create')">{{ __('Nouvelle demande') }}</x-primary-button>
        </x-slot:actions>
    </x-page-header>

    <x-portal-tabs />

    <div class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($leaveBalances as $balance)
            <div class="rounded-xl border border-line-soft bg-surface shadow-card p-4">
                <p class="text-sm font-medium text-fg">{{ $balance->leaveType->name }}</p>
                <p class="mt-1 text-2xl font-semibold text-fg">
                    {{ rtrim(rtrim(number_format($balance->availableDays(), 2), '0'), '.') }}
                    <span class="text-sm font-normal text-muted">jours</span>
                </p>
                <p class="mt-1 text-xs text-muted">
                    Acquis : {{ rtrim(rtrim(number_format($balance->accruedDays(), 2), '0'), '.') }}
                    &middot; Pris : {{ rtrim(rtrim(number_format($balance->usedDays(), 2), '0'), '.') }}
                    @if ($balance->pendingDays() > 0)
                        &middot; En attente : {{ rtrim(rtrim(number_format($balance->pendingDays(), 2), '0'), '.') }}
                    @endif
                </p>
            </div>
        @endforeach
    </div>

    <x-data-table>
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-surface-2">
                <tr>
                    <th data-sort class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Type</th>
                    <th data-sort class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Période</th>
                    <th data-sort class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Jours</th>
                    <th data-sort class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Statut</th>
                    <th class="px-4 py-2"></th>
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
                        <td class="px-4 py-3 text-sm font-medium text-fg">{{ $leaveRequest->leaveType->name }}</td>
                        <td class="px-4 py-3 text-sm text-muted"
                            data-sort-value="{{ $leaveRequest->start_date->timestamp }}">
                            {{ $leaveRequest->start_date->format('d/m/Y') }} —
                            {{ $leaveRequest->end_date->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3 text-sm text-muted">
                            {{ rtrim(rtrim(number_format($leaveRequest->days_count, 2), '0'), '.') }}</td>
                        <td class="px-4 py-3 text-sm">
                            <x-status-chip :tone="$tones[$leaveRequest->status] ?? 'neutral'">
                                {{ $leaveStatuses[$leaveRequest->status] ?? $leaveRequest->status }}
                            </x-status-chip>
                            @if ($leaveRequest->status === 'rejected' && $leaveRequest->decision_note)
                                <p class="mt-1 text-xs text-muted italic">{{ $leaveRequest->decision_note }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-sm">
                            @if ($leaveRequest->status === 'pending')
                                <form method="POST" action="{{ route('portal.leaves.destroy', $leaveRequest) }}"
                                    class="inline" data-confirm="Annuler cette demande ?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-danger hover:underline">Annuler</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr data-empty-row>
                        <td colspan="5" class="px-4 py-6 text-center text-sm text-muted">Aucune demande de congé.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-data-table>

    <x-modal name="my-leave-create" :show="$errors->isNotEmpty()" focusable>
        <form method="POST" action="{{ route('portal.leaves.store') }}" class="p-6">
            @csrf

            <h2 class="text-lg font-medium text-fg">{{ __('Nouvelle demande de congé') }}</h2>

            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <x-input-label for="my_leave_type_id" value="Type de congé" />
                    <x-select name="leave_type_id" id="my_leave_type_id" class="mt-1">
                        @foreach ($leaveTypes as $type)
                            <option value="{{ $type->id }}" @selected(old('leave_type_id') == $type->id)>
                                {{ $type->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('leave_type_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="my_leave_start_date" value="Date de début" />
                    <x-text-input name="start_date" id="my_leave_start_date" value="{{ old('start_date') }}"
                        type="date" class="mt-1" />
                    <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="my_leave_end_date" value="Date de fin" />
                    <x-text-input name="end_date" id="my_leave_end_date" value="{{ old('end_date') }}" type="date"
                        class="mt-1" />
                    <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="my_leave_reason" value="Motif (optionnel)" />
                    <textarea name="reason" id="my_leave_reason" rows="2"
                        class="mt-1 block w-full rounded-lg border-line bg-surface text-sm text-fg shadow-sm focus:border-brand focus:ring-brand">{{ old('reason') }}</textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button"
                    x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                <x-primary-button class="ms-3">{{ __('Envoyer la demande') }}</x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
