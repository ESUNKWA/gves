<x-app-layout>
    <x-page-header :title="__('Sites')" :description="__('Gérez les sites et implantations de votre organisation.')">
        <x-slot:actions>
            @can('organisation.manage')
                <x-primary-button type="button" x-data x-on:click="$dispatch('open-modal', 'site-create')">
                    {{ __('Nouveau site') }}
                </x-primary-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form method="GET" action="{{ route('organisation.sites.index') }}" class="mb-6 max-w-xs">
        <x-text-input type="search" name="search" value="{{ $search }}" class="w-full"
            placeholder="Rechercher un site..." />
    </form>

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
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Ville / Pays
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Employés
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($sites as $site)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-fg">{{ $site->name }}</td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $site->code }}</td>
                        <td class="px-6 py-4 text-sm text-muted">
                            {{ collect([$site->city, $site->country])->filter()->join(', ') ?:'—' }}</td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $site->employees_count }}</td>
                        <td class="px-6 py-4 text-sm">
                            @if ($site->is_active)
                                <x-status-chip tone="success">Actif</x-status-chip>
                            @else
                                <x-status-chip tone="neutral">Inactif</x-status-chip>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm space-x-3">
                            @can('organisation.manage')
                                <button type="button" x-data
                                    x-on:click="$dispatch('open-modal', 'site-edit-{{ $site->id }}')"
                                    class="text-brand hover:underline">Modifier</button>

                                <form method="POST" action="{{ route('organisation.sites.destroy', $site) }}"
                                    class="inline" onsubmit="return confirm('Supprimer ce site ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-danger hover:underline">Supprimer</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-muted">Aucun site pour le moment.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $sites->links() }}
    </div>

    <x-modal name="site-create" :show="old('_modal') === 'site-create'" focusable>
        <form method="POST" action="{{ route('organisation.sites.store') }}" class="p-6">
            @csrf
            <input type="hidden" name="_modal" value="site-create">

            <h2 class="text-lg font-medium text-fg">{{ __('Nouveau site') }}</h2>

            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <x-input-label for="create_name" value="Nom" />
                    <x-text-input name="name" id="create_name"
                        value="{{ old('_modal') === 'site-create' ? old('name') : '' }}" class="mt-1" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="create_code" value="Code" />
                    <x-text-input name="code" id="create_code"
                        value="{{ old('_modal') === 'site-create' ? old('code') : '' }}" class="mt-1" />
                    <x-input-error :messages="$errors->get('code')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="create_phone" value="Téléphone" />
                    <x-text-input name="phone" id="create_phone" value="{{ old('phone') }}" class="mt-1" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="create_address" value="Adresse" />
                    <x-text-input name="address" id="create_address" value="{{ old('address') }}" class="mt-1" />
                    <x-input-error :messages="$errors->get('address')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="create_city" value="Ville" />
                    <x-text-input name="city" id="create_city" value="{{ old('city') }}" class="mt-1" />
                    <x-input-error :messages="$errors->get('city')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="create_country" value="Pays" />
                    <x-country-select name="country" id="create_country" :selected="old('country')" class="mt-1" />
                    <x-input-error :messages="$errors->get('country')" class="mt-2" />
                </div>

                <div class="sm:col-span-2 flex items-center">
                    <input type="hidden" name="is_active" value="0">
                    <input name="is_active" id="create_is_active" type="checkbox" value="1" checked
                        class="rounded border-line text-brand shadow-sm focus:ring-brand">
                    <label for="create_is_active" class="ml-2 text-sm text-muted">Site actif</label>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button"
                    x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                <x-primary-button class="ms-3">{{ __('Enregistrer') }}</x-primary-button>
            </div>
        </form>
    </x-modal>

    @foreach ($sites as $site)
        <x-modal name="site-edit-{{ $site->id }}" :show="old('_modal') === 'site-edit-'.$site->id" focusable>
            <form method="POST" action="{{ route('organisation.sites.update', $site) }}" class="p-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="_modal" value="site-edit-{{ $site->id }}">

                <h2 class="text-lg font-medium text-fg">{{ __('Modifier le site') }}</h2>

                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <x-input-label for="edit_name_{{ $site->id }}" value="Nom" />
                        <x-text-input name="name" id="edit_name_{{ $site->id }}"
                            value="{{ old('_modal') === 'site-edit-' . $site->id ? old('name') : $site->name }}"
                            class="mt-1" />
                        <x-input-error :messages="old('_modal') === 'site-edit-'.$site->id ? $errors->get('name') : []" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="edit_code_{{ $site->id }}" value="Code" />
                        <x-text-input name="code" id="edit_code_{{ $site->id }}"
                            value="{{ old('_modal') === 'site-edit-' . $site->id ? old('code') : $site->code }}"
                            class="mt-1" />
                        <x-input-error :messages="old('_modal') === 'site-edit-'.$site->id ? $errors->get('code') : []" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="edit_phone_{{ $site->id }}" value="Téléphone" />
                        <x-text-input name="phone" id="edit_phone_{{ $site->id }}"
                            value="{{ old('phone', $site->phone) }}" class="mt-1" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="edit_address_{{ $site->id }}" value="Adresse" />
                        <x-text-input name="address" id="edit_address_{{ $site->id }}"
                            value="{{ old('address', $site->address) }}" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="edit_city_{{ $site->id }}" value="Ville" />
                        <x-text-input name="city" id="edit_city_{{ $site->id }}"
                            value="{{ old('city', $site->city) }}" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="edit_country_{{ $site->id }}" value="Pays" />
                        <x-country-select name="country" id="edit_country_{{ $site->id }}" :selected="old('country', $site->country)"
                            class="mt-1" />
                    </div>

                    <div class="sm:col-span-2 flex items-center">
                        <input type="hidden" name="is_active" value="0">
                        <input name="is_active" id="edit_is_active_{{ $site->id }}" type="checkbox"
                            value="1" @checked(old('is_active', $site->is_active))
                            class="rounded border-line text-brand shadow-sm focus:ring-brand">
                        <label for="edit_is_active_{{ $site->id }}" class="ml-2 text-sm text-muted">Site
                            actif</label>
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
