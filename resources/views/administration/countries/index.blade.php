<x-app-layout>
    <x-page-header :title="__('Pays')" :description="__('Liste des pays proposés dans les formulaires (adresses, fiches employé...).')">
        <x-slot:actions>
            <x-primary-button type="button" x-data x-on:click="$dispatch('open-modal', 'country-create')">
                {{ __('Ajouter un pays') }}
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Nom</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Statut
                    </th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($countries as $country)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-fg">{{ $country->name }}</td>
                        <td class="px-6 py-4 text-sm">
                            @if ($country->is_active)
                                <x-status-chip tone="success">Actif</x-status-chip>
                            @else
                                <x-status-chip tone="neutral">Inactif</x-status-chip>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm space-x-3">
                            <button type="button" x-data
                                x-on:click="$dispatch('open-modal', 'country-edit-{{ $country->id }}')"
                                class="text-brand hover:underline">Modifier</button>
                            <form method="POST" action="{{ route('administration.countries.destroy', $country) }}"
                                class="inline" onsubmit="return confirm('Supprimer ce pays ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-danger hover:underline">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-8 text-center text-sm text-muted">Aucun pays pour le moment.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-modal name="country-create" :show="old('_modal') === 'country-create'" focusable>
        <form method="POST" action="{{ route('administration.countries.store') }}" class="p-6">
            @csrf
            <input type="hidden" name="_modal" value="country-create">

            <h2 class="text-lg font-medium text-fg">{{ __('Nouveau pays') }}</h2>

            @include('administration.countries.partials.fields', [
                'id' => 'create',
                'country' => null,
                'showErrors' => old('_modal') === 'country-create',
            ])

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button"
                    x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                <x-primary-button class="ms-3">{{ __('Enregistrer') }}</x-primary-button>
            </div>
        </form>
    </x-modal>

    @foreach ($countries as $country)
        @php $isEditingThis = old('_modal') === 'country-edit-'.$country->id; @endphp
        <x-modal name="country-edit-{{ $country->id }}" :show="$isEditingThis" focusable>
            <form method="POST" action="{{ route('administration.countries.update', $country) }}" class="p-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="_modal" value="country-edit-{{ $country->id }}">

                <h2 class="text-lg font-medium text-fg">{{ __('Modifier le pays') }}</h2>

                @include('administration.countries.partials.fields', [
                    'id' => 'edit-' . $country->id,
                    'country' => $country,
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
</x-app-layout>
