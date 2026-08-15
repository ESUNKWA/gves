<x-app-layout>
    <x-page-header :title="__('Rubriques de paie')" :description="__('Configurez les gains, retenues et leurs modes de calcul.')">
        <x-slot:actions>
            <x-primary-button type="button" x-data x-on:click="$dispatch('open-modal', 'component-create')">
                {{ __('Nouvelle rubrique') }}
            </x-primary-button>
        </x-slot:actions>
    </x-page-header>

    {{--
        No <x-data-table>: its Alpine component re-sorts/repaginates the
        <tr> DOM on init and on every search/page-size change, based on a
        `rows` array snapshotted once at init — that would fight the
        drag-and-drop reorder below (whichever touches the DOM last wins).
        Rubriques lists are short by nature, so dropping client-side
        search/pagination here is a deliberate trade — drag replaces the
        column-sort this table would otherwise offer.
    --}}
    <div class="overflow-x-auto rounded-xl border border-line-soft bg-surface shadow-card">
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-surface-2">
                <tr>
                    <th class="w-8 px-3 py-3"></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Nom</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Calcul
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Statut</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line" x-data="{
                reorder() {
                    const order = Array.from(this.$el.querySelectorAll('tr[data-id]')).map((row) => Number(row.dataset.id));
                    axios.post('{{ route('payroll.components.reorder') }}', { order }).catch(() => {
                        window.Swal?.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'error',
                            text: 'L\'ordre n\'a pas pu être enregistré. Rechargez la page et réessayez.',
                            timer: 4500,
                            timerProgressBar: true,
                            showConfirmButton: false,
                        });
                    });
                },
            }" x-sort="reorder()">
                @forelse ($components as $payrollComponent)
                    <tr x-sort:item="{{ $payrollComponent->id }}" data-id="{{ $payrollComponent->id }}">
                        <td x-sort:handle class="px-3 py-4 cursor-grab text-muted active:cursor-grabbing">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8.25 6.75h.008v.008H8.25V6.75Zm0 5.25h.008v.008H8.25V12Zm0 5.25h.008v.008H8.25v-5.25Zm7.5-10.5h.008v.008h-.008V6.75Zm0 5.25h.008v.008h-.008V12Zm0 5.25h.008v.008h-.008v-5.25Z" />
                            </svg>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-fg">{{ $payrollComponent->name }}
                            <span class="text-muted">({{ $payrollComponent->code }})</span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <x-status-chip :tone="$payrollComponent->type === 'gain' ? 'success' : 'danger'">
                                {{ $types[$payrollComponent->type] ?? $payrollComponent->type }}
                            </x-status-chip>
                        </td>
                        <td class="px-6 py-4 text-sm text-muted">
                            @if ($payrollComponent->calculation_method === 'fixed')
                                Montant fixe (par employé)
                            @elseif ($payrollComponent->calculation_method === 'percentage_of_component')
                                {{ rtrim(rtrim(number_format($payrollComponent->rate, 3), '0'), '.') }}% de
                                {{ $payrollComponent->baseComponent?->name ?? '—' }}
                            @else
                                {{ rtrim(rtrim(number_format($payrollComponent->rate, 3), '0'), '.') }}% du brut
                            @endif
                            @if ($payrollComponent->ceiling_amount)
                                <br><span class="text-xs">plafonné à
                                    {{ number_format($payrollComponent->ceiling_amount, 0, ',', ' ') }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm">
                            @if ($payrollComponent->is_active)
                                <x-status-chip tone="success">Actif</x-status-chip>
                            @else
                                <x-status-chip tone="neutral">Inactif</x-status-chip>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm space-x-3">
                            @if ($payrollComponent->is_active)
                                <button type="button" x-data
                                    x-on:click="$dispatch('open-modal', 'component-assign-{{ $payrollComponent->id }}')"
                                    class="text-brand hover:underline">Assigner</button>
                            @endif

                            <button type="button" x-data
                                x-on:click="$dispatch('open-modal', 'component-edit-{{ $payrollComponent->id }}')"
                                class="text-brand hover:underline">Modifier</button>

                            @if ($payrollComponent->employee_assignments_count === 0 && $payrollComponent->payslip_lines_count === 0)
                                <form method="POST"
                                    action="{{ route('payroll.components.destroy', $payrollComponent) }}"
                                    class="inline" data-confirm="Supprimer cette rubrique ?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-danger hover:underline">Supprimer</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-muted">Aucune rubrique pour le
                            moment.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-modal name="component-create" :show="old('_modal') === 'component-create'" focusable maxWidth="4xl">
        <form method="POST" action="{{ route('payroll.components.store') }}" class="p-6">
            @csrf
            <input type="hidden" name="_modal" value="component-create">

            <h2 class="text-lg font-medium text-fg">{{ __('Nouvelle rubrique') }}</h2>

            @include('payroll.components.partials.fields', [
                'id' => 'create',
                'payrollComponent' => null,
                'showErrors' => old('_modal') === 'component-create',
                'types' => $types,
                'methods' => $methods,
                'allComponents' => $components,
                'deductionCategories' => $deductionCategories,
            ])

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button"
                    x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                <x-primary-button class="ms-3">{{ __('Enregistrer') }}</x-primary-button>
            </div>
        </form>
    </x-modal>

    @foreach ($components as $payrollComponent)
        @php $isEditingThis = old('_modal') === 'component-edit-'.$payrollComponent->id; @endphp
        <x-modal name="component-edit-{{ $payrollComponent->id }}" :show="$isEditingThis" focusable maxWidth="4xl">
            <form method="POST" action="{{ route('payroll.components.update', $payrollComponent) }}" class="p-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="_modal" value="component-edit-{{ $payrollComponent->id }}">

                <h2 class="text-lg font-medium text-fg">{{ __('Modifier la rubrique') }}</h2>

                @include('payroll.components.partials.fields', [
                    'id' => 'edit-' . $payrollComponent->id,
                    'payrollComponent' => $payrollComponent,
                    'showErrors' => $isEditingThis,
                    'types' => $types,
                    'methods' => $methods,
                    'allComponents' => $components,
                    'deductionCategories' => $deductionCategories,
                ])

                <div class="mt-6 flex justify-end">
                    <x-secondary-button type="button"
                        x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                    <x-primary-button class="ms-3">{{ __('Enregistrer') }}</x-primary-button>
                </div>
            </form>
        </x-modal>

        @if ($payrollComponent->is_active)
            @php
                $availableEmployees = $employees
                    ->whereNotIn('id', $assignedEmployeeIdsByComponent->get($payrollComponent->id, collect()))
                    ->values()
                    ->map(
                        fn($employee) => [
                            'id' => $employee->id,
                            'name' => $employee->full_name . ' (' . $employee->employee_number . ')',
                            // "Salaire de base" is pre-filled per employee from their
                            // own active contract, so HR doesn't have to retype it.
            'defaultAmount' =>
                $payrollComponent->is_base_salary && $employee->latestContract?->base_salary !== null
                    ? number_format($employee->latestContract->base_salary, 0, '', ' ')
                    : null,
            // A net-negotiated contract derives its base salary
            // automatically at every payslip run — a manually typed
            // amount here would just get overwritten, so the field
            // is shown read-only instead of editable.
            'netMode' =>
                $payrollComponent->is_base_salary &&
                $employee->latestContract?->salary_mode === \App\Models\Contract::SALARY_MODE_NET,
            'netTarget' =>
                $payrollComponent->is_base_salary &&
                $employee->latestContract?->net_salary_target !== null
                    ? number_format($employee->latestContract->net_salary_target, 0, '', ' ')
                                    : null,
                        ],
                    );
            @endphp
            <x-modal name="component-assign-{{ $payrollComponent->id }}" focusable maxWidth="4xl">
                <form method="POST" action="{{ route('payroll.components.bulk-assign', $payrollComponent) }}"
                    class="p-6" x-data="{
                        query: '',
                        selected: [],
                        employees: {{ Illuminate\Support\Js::from($availableEmployees) }},
                        get filtered() {
                            const q = this.query.trim().toLowerCase();
                            return q ? this.employees.filter((e) => e.name.toLowerCase().includes(q)) : this.employees;
                        },
                        toggleAll() {
                            // Checkbox x-model always stores values as strings (HTML attribute
                            // values), so this has to match that or selected.includes(...) below
                            // (used to enable/disable each row's amount field) silently never matches.
                            this.selected = this.selected.length === this.filtered.length ? [] : this.filtered.map((e) => String(e.id));
                        },
                    }">
                    @csrf

                    <h2 class="text-lg font-medium text-fg">
                        {{ __('Assigner') }} « {{ $payrollComponent->name }} » {{ __('à plusieurs employés') }}</h2>
                    <p class="mt-1 text-sm text-muted">Cochez un ou plusieurs employés à assigner en une seule fois.
                    </p>

                    <div class="mt-4 flex items-center justify-between gap-2">
                        <input type="search" x-model="query" placeholder="Rechercher un employé…"
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
                                    <th
                                        class="px-3 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                                        Employé</th>
                                    @if ($payrollComponent->calculation_method === 'fixed')
                                        <th
                                            class="px-3 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                                            Montant</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                <template x-for="employee in filtered" :key="employee.id">
                                    <tr>
                                        <td class="px-3 py-2">
                                            <input type="checkbox" :value="employee.id" x-model="selected"
                                                name="employee_ids[]"
                                                class="rounded border-line text-brand shadow-sm focus:ring-brand">
                                        </td>
                                        <td class="px-3 py-2 text-sm font-medium text-fg" x-text="employee.name">
                                        </td>
                                        @if ($payrollComponent->calculation_method === 'fixed')
                                            <td class="px-3 py-2">
                                                <input type="text" inputmode="numeric" data-money
                                                    :name="`amounts[${employee.id}]`" :value="employee.defaultAmount"
                                                    :disabled="!selected.includes(String(employee.id))"
                                                    :readonly="employee.netMode"
                                                    class="w-28 rounded-lg border-line bg-surface text-sm text-fg shadow-sm focus:border-brand focus:ring-brand disabled:bg-surface-2 disabled:text-faint"
                                                    :class="employee.netMode ? 'bg-surface-2 text-muted' : ''">
                                                <p x-show="employee.netMode" x-cloak
                                                    class="mt-0.5 text-[11px] text-muted">
                                                    Net visé <span x-text="employee.netTarget"></span>.
                                                </p>
                                            </td>
                                        @endif
                                    </tr>
                                </template>
                                <tr x-show="filtered.length === 0">
                                    <td colspan="3" class="px-3 py-4 text-center text-sm text-muted">Aucun employé
                                        disponible.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <p class="mt-2 text-xs text-muted"><span x-text="selected.length"></span> employé(s)
                        sélectionné(s).</p>
                    <x-input-error :messages="$errors->get('employee_ids')" class="mt-2" />
                    <x-input-error :messages="$errors->get('amounts')" class="mt-2" />

                    <div class="mt-6 flex justify-end">
                        <x-secondary-button type="button"
                            x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                        <x-primary-button class="ms-3" x-bind:disabled="selected.length === 0">
                            {{ __('Assigner') }}</x-primary-button>
                    </div>
                </form>
            </x-modal>
        @endif
    @endforeach
</x-app-layout>
