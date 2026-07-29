@php $isDraft = $payslip->status === 'draft'; @endphp

<x-app-layout>
    <x-page-header :title="$payslip->employee->full_name" :description="ucfirst($payslip->periodLabel())">
        <x-slot:actions>
            <a href="{{ route('payroll.payslips.index', ['period' => $payslip->period->format('Y-m')]) }}">
                <x-secondary-button type="button">{{ __('Retour') }}</x-secondary-button>
            </a>
            @if ($isDraft)
                <form method="POST" action="{{ route('payroll.payslips.recalculate', $payslip) }}"
                    data-confirm="Recalculer depuis la structure de rémunération ? Les lignes manuelles seront conservées, les lignes issues des rubriques seront remplacées.">
                    @csrf
                    <x-secondary-button type="submit">{{ __('Recalculer') }}</x-secondary-button>
                </form>
                <x-primary-button type="button" x-data
                    x-on:click="$dispatch('open-modal', 'validate-payslip')">{{ __('Valider le bulletin') }}</x-primary-button>
            @else
                <a href="{{ route('payroll.payslips.pdf', $payslip) }}" target="_blank">
                    <x-primary-button type="button">{{ __('Télécharger le PDF') }}</x-primary-button>
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-3">
        <x-stat-card label="Brut" :value="number_format($payslip->gross_amount, 0, ',', ' ')" />
        <x-stat-card label="Retenues" :value="number_format($payslip->deductions_amount, 0, ',', ' ')" />
        <x-stat-card label="Net à payer" :value="number_format($payslip->net_amount, 0, ',', ' ')" />
    </div>

    <x-data-table :per-page="25">
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-surface-2">
                <tr>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Rubrique</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Type</th>
                    <th data-sort class="px-6 py-3 text-right text-xs font-medium text-muted uppercase tracking-wider">
                        Montant
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($payslip->lines as $line)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-fg">{{ $line->label }}
                            @if (!$line->payroll_component_id)
                                <span class="ml-1 text-xs text-muted italic">(manuel)</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <x-status-chip :tone="$line->type === 'gain' ? 'success' : 'danger'">
                                {{ $line->type === 'gain' ? 'Gain' : 'Retenue' }}
                            </x-status-chip>
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-fg" data-sort-value="{{ $line->amount }}">
                            {{ number_format($line->amount, 0, ',', ' ') }}</td>
                    </tr>
                @empty
                    <tr data-empty-row>
                        <td colspan="3" class="px-6 py-8 text-center text-sm text-muted">Aucune ligne.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-data-table>

    @if ($isDraft)
        <div class="rounded-xl border border-line-soft bg-surface shadow-card p-6">
            <h3 class="text-base font-semibold text-fg mb-4">Ajouter une ligne manuelle</h3>
            <form method="POST" action="{{ route('payroll.payslips.lines.store', $payslip) }}"
                class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                @csrf
                <div class="sm:col-span-2">
                    <x-input-label for="line_label" value="Libellé" />
                    <x-text-input name="label" id="line_label" value="{{ old('label') }}" class="mt-1" />
                    <x-input-error :messages="$errors->get('label')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="line_type" value="Type" />
                    <x-select name="type" id="line_type" class="mt-1">
                        <option value="gain" @selected(old('type') === 'gain')>Gain</option>
                        <option value="deduction" @selected(old('type') === 'deduction')>Retenue</option>
                    </x-select>
                </div>
                <div>
                    <x-input-label for="line_amount" value="Montant" />
                    <x-text-input name="amount" id="line_amount" type="number" step="0.01" min="0"
                        value="{{ old('amount') }}" class="mt-1" />
                    <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                </div>
                <div class="sm:col-span-4">
                    <x-primary-button type="submit">{{ __('Ajouter') }}</x-primary-button>
                </div>
            </form>
        </div>

        <form method="POST" action="{{ route('payroll.payslips.destroy', $payslip) }}" class="mt-6"
            data-confirm="Supprimer ce bulletin brouillon ?">
            @csrf
            @method('DELETE')
            <x-danger-button type="submit">{{ __('Supprimer ce bulletin') }}</x-danger-button>
        </form>

        <x-modal name="validate-payslip" focusable>
            <form method="POST" action="{{ route('payroll.payslips.validate', $payslip) }}" class="p-6">
                @csrf

                <h2 class="text-lg font-medium text-fg">{{ __('Valider le bulletin') }}</h2>
                <p class="mt-1 text-sm text-muted">Une fois validé, le bulletin est verrouillé, un PDF est généré et
                    devient visible par l'employé.</p>

                <div class="mt-6 grid grid-cols-1 gap-4">
                    <div>
                        <x-input-label for="payment_method" value="Mode de paiement" />
                        <x-select name="payment_method" id="payment_method" class="mt-1">
                            <option value="Virement bancaire">Virement bancaire</option>
                            <option value="Mobile Money">Mobile Money</option>
                            <option value="Espèces">Espèces</option>
                            <option value="Chèque">Chèque</option>
                        </x-select>
                        <x-input-error :messages="$errors->get('payment_method')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="payment_date" value="Date de paiement" />
                        <x-text-input name="payment_date" id="payment_date" type="date"
                            value="{{ old('payment_date', $payslip->period->copy()->endOfMonth()->toDateString()) }}"
                            class="mt-1" />
                        <x-input-error :messages="$errors->get('payment_date')" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <x-secondary-button type="button"
                        x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                    <x-primary-button class="ms-3">{{ __('Valider') }}</x-primary-button>
                </div>
            </form>
        </x-modal>
    @endif
</x-app-layout>
