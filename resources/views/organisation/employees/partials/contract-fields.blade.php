@php $err = fn ($field) => $showErrors ? $errors->get($field) : []; @endphp

<div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="contract_type_{{ $id }}" value="Type de contrat" />
        <x-select name="contract_type" id="contract_type_{{ $id }}" class="mt-1">
            @foreach (\App\Models\Contract::types() as $value => $label)
                <option value="{{ $value }}" @selected($values['contract_type'] === $value)>{{ $label }}</option>
            @endforeach
        </x-select>
        <x-input-error :messages="$err('contract_type')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="job_title_{{ $id }}" value="Intitulé du poste" />
        <x-text-input name="job_title" id="job_title_{{ $id }}" value="{{ $values['job_title'] }}"
            class="mt-1" />
        <x-input-error :messages="$err('job_title')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="start_date_{{ $id }}" value="Date de début" />
        <x-text-input name="start_date" id="start_date_{{ $id }}" value="{{ $values['start_date'] }}"
            type="date" class="mt-1" />
        <x-input-error :messages="$err('start_date')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="end_date_{{ $id }}" value="Date de fin" />
        <x-text-input name="end_date" id="end_date_{{ $id }}" value="{{ $values['end_date'] }}"
            type="date" class="mt-1" />
        <x-input-error :messages="$err('end_date')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="trial_end_date_{{ $id }}" value="Fin de période d'essai" />
        <x-text-input name="trial_end_date" id="trial_end_date_{{ $id }}"
            value="{{ $values['trial_end_date'] }}" type="date" class="mt-1" />
        <x-input-error :messages="$err('trial_end_date')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="working_hours_per_week_{{ $id }}" value="Heures / semaine" />
        <x-text-input name="working_hours_per_week" id="working_hours_per_week_{{ $id }}"
            value="{{ $values['working_hours_per_week'] }}" type="number" class="mt-1" />
        <x-input-error :messages="$err('working_hours_per_week')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="base_salary_{{ $id }}" value="Salaire de base" />
        <x-text-input name="base_salary" id="base_salary_{{ $id }}" value="{{ $values['base_salary'] }}"
            type="number" step="0.01" class="mt-1" />
        <x-input-error :messages="$err('base_salary')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="currency_{{ $id }}" value="Devise" />
        <x-text-input name="currency" id="currency_{{ $id }}" value="{{ $values['currency'] }}"
            class="mt-1" />
        <x-input-error :messages="$err('currency')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="status_{{ $id }}" value="Statut" />
        <x-select name="status" id="status_{{ $id }}" class="mt-1">
            @foreach (\App\Models\Contract::statuses() as $value => $label)
                <option value="{{ $value }}" @selected($values['status'] === $value)>{{ $label }}</option>
            @endforeach
        </x-select>
        <x-input-error :messages="$err('status')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="document_{{ $id }}" value="Document (PDF, image)" />
        <input name="document" id="document_{{ $id }}" type="file"
            class="mt-1 block w-full text-sm text-muted">
        <x-input-error :messages="$err('document')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="notes_{{ $id }}" value="Notes" />
        <textarea name="notes" id="notes_{{ $id }}" rows="2"
            class="mt-1 block w-full rounded-lg border-line bg-surface text-sm text-fg shadow-sm focus:border-brand focus:ring-brand dark:focus:border-brand dark:focus:ring-brand">{{ $values['notes'] }}</textarea>
        <x-input-error :messages="$err('notes')" class="mt-2" />
    </div>
</div>
