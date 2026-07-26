@php
    $val = fn($field, $default = null) => $showErrors ? old($field, $default) : $default;
    $err = fn($field) => $showErrors ? $errors->get($field) : [];
@endphp

<div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="name_{{ $id }}" value="Nom" />
        <x-text-input name="name" id="name_{{ $id }}" value="{{ $val('name', $leaveType?->name) }}"
            class="mt-1" />
        <x-input-error :messages="$err('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="code_{{ $id }}" value="Code" />
        <x-text-input name="code" id="code_{{ $id }}" value="{{ $val('code', $leaveType?->code) }}"
            class="mt-1" />
        <x-input-error :messages="$err('code')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="accrual_{{ $id }}" value="Acquisition (jours / mois)" />
        <x-text-input name="accrual_days_per_month" id="accrual_{{ $id }}"
            value="{{ $val('accrual_days_per_month', $leaveType?->accrual_days_per_month) }}" type="number"
            step="0.01" min="0" max="31" placeholder="Laisser vide = acquisition manuelle"
            class="mt-1" />
        <x-input-error :messages="$err('accrual_days_per_month')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="carry_over_{{ $id }}" value="Report max (jours)" />
        <x-text-input name="max_carry_over_days" id="carry_over_{{ $id }}"
            value="{{ $val('max_carry_over_days', $leaveType?->max_carry_over_days) }}" type="number" min="0"
            placeholder="Laisser vide = illimité" class="mt-1" />
        <x-input-error :messages="$err('max_carry_over_days')" class="mt-2" />
    </div>

    <div class="flex items-center">
        <input type="hidden" name="is_paid" value="0">
        <input name="is_paid" id="is_paid_{{ $id }}" type="checkbox" value="1"
            @checked($val('is_paid', $leaveType?->is_paid ?? true)) class="rounded border-line text-brand shadow-sm focus:ring-brand">
        <label for="is_paid_{{ $id }}" class="ml-2 text-sm text-muted">Rémunéré</label>
    </div>

    <div class="flex items-center">
        <input type="hidden" name="requires_approval" value="0">
        <input name="requires_approval" id="requires_approval_{{ $id }}" type="checkbox" value="1"
            @checked($val('requires_approval', $leaveType?->requires_approval ?? true)) class="rounded border-line text-brand shadow-sm focus:ring-brand">
        <label for="requires_approval_{{ $id }}" class="ml-2 text-sm text-muted">Nécessite une
            approbation</label>
    </div>

    <div class="flex items-center">
        <input type="hidden" name="is_active" value="0">
        <input name="is_active" id="is_active_{{ $id }}" type="checkbox" value="1"
            @checked($val('is_active', $leaveType?->is_active ?? true)) class="rounded border-line text-brand shadow-sm focus:ring-brand">
        <label for="is_active_{{ $id }}" class="ml-2 text-sm text-muted">Actif</label>
    </div>
</div>
