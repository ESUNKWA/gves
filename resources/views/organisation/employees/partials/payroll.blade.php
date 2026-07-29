@php $isAssigning = old('_modal') === 'pay-component-assign'; @endphp

<div class="flex items-center justify-between mb-4">
    <h3 class="text-base font-semibold text-fg">Structure de rémunération</h3>
    @if ($availablePayrollComponents->isNotEmpty())
        <x-secondary-button type="button" x-data
            x-on:click="$dispatch('open-modal', 'pay-component-assign')">{{ __('Assigner une rubrique') }}</x-secondary-button>
    @endif
</div>

<x-data-table>
    <table class="min-w-full divide-y divide-line">
        <thead class="bg-surface-2">
            <tr>
                <th data-sort class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                    Rubrique</th>
                <th data-sort class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                    Type</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">Montant /
                    taux</th>
                <th data-sort class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                    Statut</th>
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
                            @if (
                                $payrollComponent->is_base_salary &&
                                    $employee->latestContract?->salary_mode === \App\Models\Contract::SALARY_MODE_NET)
                                <span
                                    class="font-medium text-fg">{{ number_format($assignment->amount, 0, ',', ' ') }}</span>
                                <span class="block text-xs text-muted">Calculé automatiquement pour un net visé de
                                    {{ number_format($employee->latestContract->net_salary_target, 0, ',', ' ') }}</span>
                            @else
                                <form method="POST"
                                    action="{{ route('organisation.employees.pay-components.update', [$employee, $assignment]) }}"
                                    class="flex items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" inputmode="numeric" data-money name="amount"
                                        value="{{ $assignment->amount !== null ? number_format($assignment->amount, 0, '', ' ') : '' }}"
                                        class="w-32 rounded-lg border-line bg-surface text-sm text-fg shadow-sm focus:border-brand focus:ring-brand">
                                    <input type="hidden" name="is_active" value="{{ $assignment->is_active ? 1 : 0 }}">
                                    <button type="submit"
                                        class="text-xs text-brand hover:underline">Enregistrer</button>
                                </form>
                            @endif
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
                            class="inline" data-confirm="Retirer cette rubrique ?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-danger hover:underline">Retirer</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr data-empty-row>
                    <td colspan="5" class="px-4 py-6 text-center text-sm text-muted">Aucune rubrique assignée à cet
                        employé.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</x-data-table>

<h3 class="text-base font-semibold text-fg mb-4">Historique des bulletins</h3>

<x-data-table>
    <table class="min-w-full divide-y divide-line">
        <thead class="bg-surface-2">
            <tr>
                <th data-sort class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                    Période</th>
                <th data-sort class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                    Brut</th>
                <th data-sort class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                    Retenues</th>
                <th data-sort class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                    Net</th>
                <th data-sort class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                    Statut</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-line">
            @forelse ($payslips as $payslip)
                <tr>
                    <td class="px-4 py-3 text-sm font-medium text-fg"
                        data-sort-value="{{ $payslip->period->timestamp }}">
                        {{ ucfirst($payslip->periodLabel()) }}</td>
                    <td class="px-4 py-3 text-sm text-muted" data-sort-value="{{ $payslip->gross_amount }}">
                        {{ number_format($payslip->gross_amount, 0, ',', ' ') }}
                    </td>
                    <td class="px-4 py-3 text-sm text-muted" data-sort-value="{{ $payslip->deductions_amount }}">
                        {{ number_format($payslip->deductions_amount, 0, ',', ' ') }}</td>
                    <td class="px-4 py-3 text-sm font-medium text-fg" data-sort-value="{{ $payslip->net_amount }}">
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
                <tr data-empty-row>
                    <td colspan="6" class="px-4 py-6 text-center text-sm text-muted">Aucun bulletin généré.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</x-data-table>

@if ($availablePayrollComponents->isNotEmpty())
    <x-modal name="pay-component-assign" :show="$isAssigning" focusable maxWidth="4xl">
        <form method="POST" action="{{ route('organisation.employees.pay-components.store', $employee) }}"
            class="p-6" x-data="{
                query: '',
                selected: [],
                components: {{ Illuminate\Support\Js::from(
                    $availablePayrollComponents->map(
                        fn($c) => [
                            'id' => $c->id,
                            'name' => $c->name,
                            'type' => $c->type,
                            'method' => $c->calculation_method,
                            'rate' => $c->rate !== null ? rtrim(rtrim(number_format($c->rate, 3), '0'), '.') : null,
                            // Pre-fill "Salaire de base" from the employee's active
                            // contract, so HR doesn't have to retype it by hand.
                            'defaultAmount' =>
                                $c->is_base_salary && $employee->latestContract?->base_salary !== null
                                    ? number_format($employee->latestContract->base_salary, 0, '', ' ')
                                    : null,
                            // A net-negotiated contract derives its base salary
                            // automatically at every payslip run — a manually typed
                            // amount here would just get overwritten, so the field
                            // is shown read-only instead of editable.
                            'netMode' =>
                                $c->is_base_salary && $employee->latestContract?->salary_mode === \App\Models\Contract::SALARY_MODE_NET,
                            'netTarget' =>
                                $c->is_base_salary && $employee->latestContract?->net_salary_target !== null
                                    ? number_format($employee->latestContract->net_salary_target, 0, '', ' ')
                                    : null,
                        ],
                    ),
                ) }},
                get filtered() {
                    const q = this.query.trim().toLowerCase();
                    return q ? this.components.filter((c) => c.name.toLowerCase().includes(q)) : this.components;
                },
                toggleAll() {
                    // Checkbox x-model always stores values as strings (HTML
                    // attribute values), so this has to match that or the
                    // per-row amount field's :disabled check (and any other
                    // selected.includes(...) comparison) silently never matches.
                    this.selected = this.selected.length === this.filtered.length ? [] : this.filtered.map((c) => String(c.id));
                },
            }">
            @csrf
            <input type="hidden" name="_modal" value="pay-component-assign">

            <h2 class="text-lg font-medium text-fg">{{ __('Assigner une rubrique') }}</h2>
            <p class="mt-1 text-sm text-muted">Cochez une ou plusieurs rubriques à assigner à
                {{ $employee->full_name }} en une seule fois.</p>

            <div class="mt-4 flex items-center justify-between gap-2">
                <input type="search" x-model="query" placeholder="Rechercher une rubrique…"
                    class="w-full rounded-lg border-line bg-surface text-sm text-fg shadow-sm focus:border-brand focus:ring-brand">
                <button type="button" x-on:click="toggleAll()"
                    class="shrink-0 whitespace-nowrap text-xs text-brand hover:underline">
                    <span
                        x-text="selected.length > 0 && selected.length === filtered.length ? 'Tout désélectionner' : 'Tout sélectionner'"></span>
                </button>
            </div>

            <div class="mt-2 max-h-80 overflow-y-auto rounded-lg border border-line-soft">
                <table class="min-w-full divide-y divide-line">
                    <thead class="sticky top-0 bg-surface-2">
                        <tr>
                            <th class="w-8 px-3 py-2"></th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                                Rubrique</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                                Type</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                                Montant / taux</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <template x-for="component in filtered" :key="component.id">
                            <tr>
                                <td class="px-3 py-2">
                                    <input type="checkbox" :value="component.id" x-model="selected"
                                        name="payroll_component_ids[]"
                                        class="rounded border-line text-brand shadow-sm focus:ring-brand">
                                </td>
                                <td class="px-3 py-2 text-sm font-medium text-fg" x-text="component.name"></td>
                                <td class="px-3 py-2 text-sm">
                                    <span :class="component.type === 'gain' ? 'text-success' : 'text-danger'"
                                        x-text="component.type === 'gain' ? 'Gain' : 'Retenue'"></span>
                                </td>
                                <td class="px-3 py-2 text-sm">
                                    <template x-if="component.method === 'fixed'">
                                        <div>
                                            <input type="text" inputmode="numeric" data-money
                                                :name="`amounts[${component.id}]`" :value="component.defaultAmount"
                                                :disabled="!selected.includes(String(component.id))"
                                                :readonly="component.netMode"
                                                class="w-28 rounded-lg border-line bg-surface text-sm text-fg shadow-sm focus:border-brand focus:ring-brand disabled:bg-surface-2 disabled:text-faint"
                                                :class="component.netMode ? 'bg-surface-2 text-muted' : ''">
                                            <p x-show="component.netMode" x-cloak
                                                class="mt-0.5 text-[11px] text-muted">
                                                Calculé pour un net visé de <span x-text="component.netTarget"></span>.
                                            </p>
                                        </div>
                                    </template>
                                    <span class="text-muted"
                                        x-text="component.method !== 'fixed' ? component.rate + '%' : ''"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <p class="mt-2 text-xs text-muted"><span x-text="selected.length"></span> rubrique(s)
                sélectionnée(s).</p>
            <x-input-error :messages="$isAssigning ? $errors->get('payroll_component_ids') : []" class="mt-2" />
            <x-input-error :messages="$isAssigning ? $errors->get('amounts') : []" class="mt-2" />

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button"
                    x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                <x-primary-button class="ms-3" x-bind:disabled="selected.length === 0">
                    {{ __('Assigner') }}</x-primary-button>
            </div>
        </form>
    </x-modal>
@endif
