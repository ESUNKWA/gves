@php
    $val = fn($field, $default = null) => $showErrors ? old($field, $default) : $default;
    $err = fn($field) => $showErrors ? $errors->get($field) : [];
@endphp

<div x-data="{
    method: '{{ $val('calculation_method', $payrollComponent?->calculation_method ?? 'fixed') }}',
    type: '{{ $val('type', $payrollComponent?->type ?? 'gain') }}',
}" class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="name_{{ $id }}" value="Nom de la rubrique" />
        <x-text-input name="name" id="name_{{ $id }}" value="{{ $val('name', $payrollComponent?->name) }}"
            class="mt-1" />
        <x-input-error :messages="$err('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="code_{{ $id }}" value="Code" />
        <x-text-input name="code" id="code_{{ $id }}" value="{{ $val('code', $payrollComponent?->code) }}"
            class="mt-1" />
        <x-input-error :messages="$err('code')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="type_{{ $id }}" value="Type" />
        <x-select name="type" id="type_{{ $id }}" x-model="type" class="mt-1">
            @foreach ($types as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-select>
        <x-input-error :messages="$err('type')" class="mt-2" />
    </div>

    <div x-show="type === 'deduction'" x-cloak>
        <x-input-label for="deduction_category_{{ $id }}" value="Catégorie (cumuls annuels)" />
        <x-select name="deduction_category" id="deduction_category_{{ $id }}" class="mt-1">
            <option value="">—</option>
            @foreach ($deductionCategories as $value => $label)
                <option value="{{ $value }}" @selected($val('deduction_category', $payrollComponent?->deduction_category) === $value)>
                    {{ $label }}</option>
            @endforeach
        </x-select>
        <x-input-error :messages="$err('deduction_category')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="order_{{ $id }}" value="Ordre d'affichage" />
        <x-text-input name="order" id="order_{{ $id }}" type="number" min="0"
            value="{{ $val('order', $payrollComponent?->order ?? 0) }}" class="mt-1" />
        <x-input-error :messages="$err('order')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="calculation_method_{{ $id }}" value="Mode de calcul" />
        <x-select name="calculation_method" id="calculation_method_{{ $id }}" x-model="method"
            class="mt-1">
            @foreach ($methods as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-select>
        <x-input-error :messages="$err('calculation_method')" class="mt-2" />
        <p class="mt-1 text-xs text-muted">
            "Montant fixe" : saisi individuellement pour chaque employé. "Pourcentage" : le taux ci-dessous
            s'applique automatiquement à tous les employés assignés à cette rubrique.
        </p>
    </div>

    <div x-show="method === 'percentage_of_component'" x-cloak>
        <x-input-label for="base_component_id_{{ $id }}" value="Rubrique de référence" />
        <x-select name="base_component_id" id="base_component_id_{{ $id }}" class="mt-1">
            <option value="">—</option>
            @foreach ($allComponents as $other)
                @continue($payrollComponent && $other->id === $payrollComponent->id)
                <option value="{{ $other->id }}" @selected($val('base_component_id', $payrollComponent?->base_component_id) == $other->id)>
                    {{ $other->name }}</option>
            @endforeach
        </x-select>
        <x-input-error :messages="$err('base_component_id')" class="mt-2" />
    </div>

    <div x-show="method !== 'fixed'" x-cloak>
        <x-input-label for="rate_{{ $id }}" value="Taux (%)" />
        <x-text-input name="rate" id="rate_{{ $id }}" type="number" step="0.001" min="0"
            max="100" value="{{ $val('rate', $payrollComponent?->rate) }}" class="mt-1" />
        <x-input-error :messages="$err('rate')" class="mt-2" />
    </div>

    <div x-show="method !== 'fixed'" x-cloak>
        <x-input-label for="ceiling_amount_{{ $id }}" value="Plafond (optionnel)" />
        <x-text-input name="ceiling_amount" id="ceiling_amount_{{ $id }}" type="number" step="0.01"
            min="0" value="{{ $val('ceiling_amount', $payrollComponent?->ceiling_amount) }}"
            placeholder="Laisser vide = illimité" class="mt-1" />
        <x-input-error :messages="$err('ceiling_amount')" class="mt-2" />
    </div>

    <div class="flex items-center">
        <input type="hidden" name="is_active" value="0">
        <input name="is_active" id="is_active_{{ $id }}" type="checkbox" value="1"
            @checked($val('is_active', $payrollComponent?->is_active ?? true)) class="rounded border-line text-brand shadow-sm focus:ring-brand">
        <label for="is_active_{{ $id }}" class="ml-2 text-sm text-muted">Actif</label>
    </div>

    <div class="flex items-center" x-show="type === 'gain'" x-cloak>
        <input type="hidden" name="is_base_salary" value="0">
        <input name="is_base_salary" id="is_base_salary_{{ $id }}" type="checkbox" value="1"
            @checked($val('is_base_salary', $payrollComponent?->is_base_salary ?? false)) class="rounded border-line text-brand shadow-sm focus:ring-brand">
        <label for="is_base_salary_{{ $id }}" class="ml-2 text-sm text-muted">Salaire de base (exclu des
            "primes
            cumulées")</label>
    </div>

    <div class="flex items-center sm:col-span-2" x-show="type === 'gain'" x-cloak>
        <input type="hidden" name="is_subject_to_contributions" value="0">
        <input name="is_subject_to_contributions" id="is_subject_to_contributions_{{ $id }}"
            type="checkbox" value="1" @checked($val('is_subject_to_contributions', $payrollComponent?->is_subject_to_contributions ?? true))
            class="rounded border-line text-brand shadow-sm focus:ring-brand">
        <label for="is_subject_to_contributions_{{ $id }}" class="ml-2 text-sm text-muted">Soumis aux
            cotisations/impôts (décocher pour une prime exonérée, ex: transport non imposable)</label>
    </div>
</div>
