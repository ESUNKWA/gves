@php $isAssigning = old('_modal') === 'pay-component-assign'; @endphp

<div class="flex items-center justify-between mb-4">
    <h3 class="text-base font-semibold text-fg">Structure de rémunération</h3>
    @if ($availablePayrollComponents->isNotEmpty())
        <x-secondary-button type="button" x-data
            x-on:click="$dispatch('open-modal', 'pay-component-assign')">{{ __('Assigner une rubrique') }}</x-secondary-button>
    @endif
</div>

<div class="rounded-xl border border-line-soft bg-surface shadow-card overflow-hidden mb-8">
    <table class="min-w-full divide-y divide-line">
        <thead class="bg-surface-2">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">Rubrique</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">Type</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">Montant /
                    taux</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">Statut</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-line">
            @forelse ($employeePayComponents as $assignment)
                @php $payrollComponent = $assignment->payrollComponent; @endphp
                <tr>
                    <td class="px-4 py-3 text-sm font-medium text-fg">{{ $payrollComponent->name }}</td>
                    <td class="px-4 py-3 text-sm">
                        <x-status-chip :tone="$payrollComponent->type === 'gain' ? 'success' : 'danger'">
                            {{ $payrollComponent->type === 'gain' ? 'Gain' : 'Retenue' }}
                        </x-status-chip>
                    </td>
                    <td class="px-4 py-3 text-sm text-muted">
                        @if ($payrollComponent->calculation_method === 'fixed')
                            <form method="POST"
                                action="{{ route('organisation.employees.pay-components.update', [$employee, $assignment]) }}"
                                class="flex items-center gap-2">
                                @csrf
                                @method('PUT')
                                <input type="number" step="0.01" min="0" name="amount"
                                    value="{{ $assignment->amount }}"
                                    class="w-32 rounded-lg border-line bg-surface text-sm text-fg shadow-sm focus:border-brand focus:ring-brand">
                                <input type="hidden" name="is_active" value="{{ $assignment->is_active ? 1 : 0 }}">
                                <button type="submit" class="text-xs text-brand hover:underline">Enregistrer</button>
                            </form>
                        @else
                            {{ rtrim(rtrim(number_format($payrollComponent->rate, 3), '0'), '.') }}%
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm">
                        @if ($assignment->is_active)
                            <x-status-chip tone="success">Actif</x-status-chip>
                        @else
                            <x-status-chip tone="neutral">Inactif</x-status-chip>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right text-sm">
                        <form method="POST"
                            action="{{ route('organisation.employees.pay-components.destroy', [$employee, $assignment]) }}"
                            class="inline" onsubmit="return confirm('Retirer cette rubrique ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-danger hover:underline">Retirer</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-sm text-muted">Aucune rubrique assignée à cet
                        employé.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<h3 class="text-base font-semibold text-fg mb-4">Historique des bulletins</h3>

<div class="rounded-xl border border-line-soft bg-surface shadow-card overflow-hidden">
    <table class="min-w-full divide-y divide-line">
        <thead class="bg-surface-2">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">Période</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">Brut</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">Retenues</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">Net</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">Statut</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-line">
            @forelse ($payslips as $payslip)
                <tr>
                    <td class="px-4 py-3 text-sm font-medium text-fg">{{ ucfirst($payslip->periodLabel()) }}</td>
                    <td class="px-4 py-3 text-sm text-muted">{{ number_format($payslip->gross_amount, 0, ',', ' ') }}
                    </td>
                    <td class="px-4 py-3 text-sm text-muted">
                        {{ number_format($payslip->deductions_amount, 0, ',', ' ') }}</td>
                    <td class="px-4 py-3 text-sm font-medium text-fg">
                        {{ number_format($payslip->net_amount, 0, ',', ' ') }}</td>
                    <td class="px-4 py-3 text-sm">
                        <x-status-chip :tone="$payslip->status === 'validated' ? 'success' : 'warning'">
                            {{ $payslip->status === 'validated' ? 'Validé' : 'Brouillon' }}
                        </x-status-chip>
                    </td>
                    <td class="px-4 py-3 text-right text-sm">
                        <a href="{{ route('payroll.payslips.show', $payslip) }}"
                            class="text-brand hover:underline">Voir</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-sm text-muted">Aucun bulletin généré.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($availablePayrollComponents->isNotEmpty())
    <x-modal name="pay-component-assign" :show="$isAssigning" focusable>
        <form method="POST" action="{{ route('organisation.employees.pay-components.store', $employee) }}"
            class="p-6">
            @csrf
            <input type="hidden" name="_modal" value="pay-component-assign">

            <h2 class="text-lg font-medium text-fg">{{ __('Assigner une rubrique') }}</h2>

            <div class="mt-6 grid grid-cols-1 gap-4" x-data="{
                components: {{ $availablePayrollComponents->keyBy('id')->map->only(['calculation_method'])->toJson() }},
                selected: '{{ old('payroll_component_id', $availablePayrollComponents->first()?->id) }}',
            }">
                <div>
                    <x-input-label for="assign_component" value="Rubrique" />
                    <x-select name="payroll_component_id" id="assign_component" x-model="selected" class="mt-1">
                        @foreach ($availablePayrollComponents as $available)
                            <option value="{{ $available->id }}">{{ $available->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$isAssigning ? $errors->get('payroll_component_id') : []" class="mt-2" />
                </div>

                <div x-show="components[selected]?.calculation_method === 'fixed'" x-cloak>
                    <x-input-label for="assign_amount" value="Montant" />
                    <x-text-input name="amount" id="assign_amount" type="number" step="0.01" min="0"
                        value="{{ old('amount') }}" class="mt-1" />
                    <x-input-error :messages="$isAssigning ? $errors->get('amount') : []" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button"
                    x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                <x-primary-button class="ms-3">{{ __('Assigner') }}</x-primary-button>
            </div>
        </form>
    </x-modal>
@endif
