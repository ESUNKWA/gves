<x-app-layout>
    <x-page-header :title="__('Jours fériés')" :description="__('Utilisés pour exclure ces jours des calculs de présence sur les bulletins de paie.')">
        <x-slot:actions>
            <x-primary-button type="button" x-data x-on:click="$dispatch('open-modal', 'holiday-create')">
                {{ __('Ajouter un jour férié') }}
            </x-primary-button>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <div class="mb-4 p-3 rounded-lg bg-success-soft text-success text-sm">
            {{ session('status') }}
        </div>
    @endif

    <div class="rounded-xl border border-line-soft bg-surface shadow-card overflow-hidden">
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-surface-2">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Date
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Libellé
                    </th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($holidays as $holiday)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-fg">{{ $holiday->date->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $holiday->name }}</td>
                        <td class="px-6 py-4 text-right text-sm space-x-3">
                            <button type="button" x-data
                                x-on:click="$dispatch('open-modal', 'holiday-edit-{{ $holiday->id }}')"
                                class="text-brand hover:underline">Modifier</button>
                            <form method="POST" action="{{ route('administration.holidays.destroy', $holiday) }}"
                                class="inline" onsubmit="return confirm('Supprimer ce jour férié ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-danger hover:underline">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-8 text-center text-sm text-muted">Aucun jour férié configuré.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-modal name="holiday-create" :show="old('_modal') === 'holiday-create'" focusable>
        <form method="POST" action="{{ route('administration.holidays.store') }}" class="p-6">
            @csrf
            <input type="hidden" name="_modal" value="holiday-create">

            <h2 class="text-lg font-medium text-fg">{{ __('Nouveau jour férié') }}</h2>

            <div class="mt-6 grid grid-cols-1 gap-4">
                <div>
                    <x-input-label for="create_holiday_date" value="Date" />
                    <x-text-input name="date" id="create_holiday_date" type="date" value="{{ old('date') }}"
                        class="mt-1" />
                    <x-input-error :messages="$errors->get('date')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="create_holiday_name" value="Libellé" />
                    <x-text-input name="name" id="create_holiday_name" value="{{ old('name') }}" class="mt-1" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button"
                    x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                <x-primary-button class="ms-3">{{ __('Enregistrer') }}</x-primary-button>
            </div>
        </form>
    </x-modal>

    @foreach ($holidays as $holiday)
        @php $isEditingThis = old('_modal') === 'holiday-edit-'.$holiday->id; @endphp
        <x-modal name="holiday-edit-{{ $holiday->id }}" :show="$isEditingThis" focusable>
            <form method="POST" action="{{ route('administration.holidays.update', $holiday) }}" class="p-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="_modal" value="holiday-edit-{{ $holiday->id }}">

                <h2 class="text-lg font-medium text-fg">{{ __('Modifier le jour férié') }}</h2>

                <div class="mt-6 grid grid-cols-1 gap-4">
                    <div>
                        <x-input-label for="edit_holiday_date_{{ $holiday->id }}" value="Date" />
                        <x-text-input name="date" id="edit_holiday_date_{{ $holiday->id }}" type="date"
                            value="{{ old('date', $holiday->date->toDateString()) }}" class="mt-1" />
                        <x-input-error :messages="$isEditingThis ? $errors->get('date') : []" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="edit_holiday_name_{{ $holiday->id }}" value="Libellé" />
                        <x-text-input name="name" id="edit_holiday_name_{{ $holiday->id }}"
                            value="{{ old('name', $holiday->name) }}" class="mt-1" />
                        <x-input-error :messages="$isEditingThis ? $errors->get('name') : []" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <x-secondary-button type="button"
                        x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                    <x-primary-button class="ms-3">{{ __('Enregistrer') }}</x-primary-button>
                </div>
            </form>
        </x-modal>
    @endforeach
</x-app-layout>
