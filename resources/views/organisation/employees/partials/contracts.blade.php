<div class="flex items-center justify-between mb-4">
    <h3 class="text-base font-semibold text-fg">Contrats</h3>
    @can('employees.manage')
        <x-secondary-button type="button" x-data
            x-on:click="$dispatch('open-modal', 'contract-create')">{{ __('Nouveau contrat') }}</x-secondary-button>
    @endcan
</div>

<x-data-table>
    <table class="min-w-full divide-y divide-line">
        <thead class="bg-surface-2">
            <tr>
                <th data-sort class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">Type
                </th>
                <th data-sort class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                    Période</th>
                <th data-sort class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                    Salaire de base</th>
                <th data-sort class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                    Statut</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-line">
            @forelse ($contracts as $contract)
                <tr>
                    <td class="px-4 py-3 text-sm font-medium text-fg">
                        {{ $contractTypes[$contract->contract_type] ?? $contract->contract_type }}</td>
                    <td class="px-4 py-3 text-sm text-muted" data-sort-value="{{ $contract->start_date->timestamp }}">
                        {{ $contract->start_date->format('d/m/Y') }} —
                        {{ $contract->end_date?->format('d/m/Y') ?? 'indéterminé' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-muted">
                        @if ($contract->base_salary)
                            {{ number_format($contract->base_salary, 0, ',', ' ') }} {{ $contract->currency }}
                            @if ($contract->salary_mode === \App\Models\Contract::SALARY_MODE_NET)
                                <span class="block text-xs text-muted">négocié net
                                    {{ number_format($contract->net_salary_target, 0, ',', ' ') }}</span>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm">
                        @php
                            $contractTones = [
                                'draft' => 'neutral',
                                'active' => 'success',
                                'ended' => 'neutral',
                                'terminated' => 'danger',
                            ];
                        @endphp
                        <x-status-chip :tone="$contractTones[$contract->status] ?? 'neutral'">
                            {{ $contractStatuses[$contract->status] ?? $contract->status }}
                        </x-status-chip>
                    </td>
                    <td class="px-4 py-3 text-right text-sm space-x-3">
                        @if ($contract->document_path)
                            <a href="{{ route('organisation.employees.contracts.download', [$employee, $contract]) }}"
                                class="text-brand hover:underline">Document</a>
                        @endif
                        @can('employees.manage')
                            <button type="button" x-data
                                x-on:click="$dispatch('open-modal', 'contract-edit-{{ $contract->id }}')"
                                class="text-brand hover:underline">Modifier</button>

                            <form method="POST"
                                action="{{ route('organisation.employees.contracts.destroy', [$employee, $contract]) }}"
                                class="inline" data-confirm="Supprimer ce contrat ?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-danger hover:underline">Supprimer</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr data-empty-row>
                    <td colspan="5" class="px-4 py-6 text-center text-sm text-muted">Aucun contrat enregistré.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</x-data-table>

@php
    // old() input must only apply to the single form that actually submitted (identified by
    // the hidden `_modal` field), otherwise a validation error on one contract's edit form
// would leak that old input into every other contract form sharing the same field names.
$contractFields = function ($contract, bool $isThisForm) {
    $val = fn($field, $default = null) => $isThisForm ? old($field, $default) : $default;

    return [
        'contract_type' => $val('contract_type', $contract?->contract_type ?? \App\Models\Contract::TYPE_CDI),
        'job_title' => $val('job_title', $contract?->job_title),
        'start_date' => $val('start_date', $contract?->start_date?->toDateString() ?? now()->toDateString()),
        'end_date' => $val('end_date', $contract?->end_date?->toDateString()),
        'trial_end_date' => $val('trial_end_date', $contract?->trial_end_date?->toDateString()),
        'working_hours_per_week' => $val('working_hours_per_week', $contract?->working_hours_per_week),
        'base_salary' => $val('base_salary', $contract?->base_salary),
        'salary_mode' => $val('salary_mode', $contract?->salary_mode ?? \App\Models\Contract::SALARY_MODE_GROSS),
        'net_salary_target' => $val('net_salary_target', $contract?->net_salary_target),
        'currency' => $val('currency', $contract?->currency ?? 'XOF'),
        'status' => $val('status', $contract?->status ?? \App\Models\Contract::STATUS_DRAFT),
        'notes' => $val('notes', $contract?->notes),
    ];
};
$isCreating = old('_modal') === 'contract-create';
@endphp

<x-modal name="contract-create" :show="$isCreating" focusable maxWidth="lg">
    <form method="POST" action="{{ route('organisation.employees.contracts.store', $employee) }}"
        enctype="multipart/form-data" class="p-6">
        @csrf
        <input type="hidden" name="_modal" value="contract-create">
        <x-input-label value="Nouveau contrat" class="text-lg font-medium text-fg" />
        @include('organisation.employees.partials.contract-fields', [
            'id' => 'create',
            'values' => $contractFields(null, $isCreating),
            'showErrors' => $isCreating,
        ])
        <div class="mt-6 flex justify-end">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
            <x-primary-button class="ms-3">{{ __('Enregistrer') }}</x-primary-button>
        </div>
    </form>
</x-modal>

@foreach ($contracts as $contract)
    @php $isEditingThis = old('_modal') === 'contract-edit-'.$contract->id; @endphp
    <x-modal name="contract-edit-{{ $contract->id }}" :show="$isEditingThis" focusable maxWidth="lg">
        <form method="POST" action="{{ route('organisation.employees.contracts.update', [$employee, $contract]) }}"
            enctype="multipart/form-data" class="p-6">
            @csrf
            @method('PUT')
            <input type="hidden" name="_modal" value="contract-edit-{{ $contract->id }}">
            <x-input-label value="Modifier le contrat" class="text-lg font-medium text-fg" />
            @include('organisation.employees.partials.contract-fields', [
                'id' => 'edit-' . $contract->id,
                'values' => $contractFields($contract, $isEditingThis),
                'showErrors' => $isEditingThis,
            ])
            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button"
                    x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                <x-primary-button class="ms-3">{{ __('Enregistrer') }}</x-primary-button>
            </div>
        </form>
    </x-modal>
@endforeach
