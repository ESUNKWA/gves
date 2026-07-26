<x-app-layout>
    <x-page-header :title="__('Rubriques de paie')" :description="__('Configurez les gains, retenues et leurs modes de calcul.')">
        <x-slot:actions>
            <x-primary-button type="button" x-data x-on:click="$dispatch('open-modal', 'component-create')">
                {{ __('Nouvelle rubrique') }}
            </x-primary-button>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <div class="mb-4 p-3 rounded-lg bg-success-soft text-success text-sm">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 p-3 rounded-lg bg-danger-soft text-danger text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-xl border border-line-soft bg-surface shadow-card overflow-hidden">
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-surface-2">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Nom</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Type
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Calcul
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Statut
                    </th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($components as $payrollComponent)
                    <tr>
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
                            <button type="button" x-data
                                x-on:click="$dispatch('open-modal', 'component-edit-{{ $payrollComponent->id }}')"
                                class="text-brand hover:underline">Modifier</button>

                            @if ($payrollComponent->employee_assignments_count === 0 && $payrollComponent->payslip_lines_count === 0)
                                <form method="POST"
                                    action="{{ route('payroll.components.destroy', $payrollComponent) }}"
                                    class="inline" onsubmit="return confirm('Supprimer cette rubrique ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-danger hover:underline">Supprimer</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-muted">Aucune rubrique pour le
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
    @endforeach
</x-app-layout>
