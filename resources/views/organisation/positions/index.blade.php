<x-app-layout>
    <x-page-header :title="__('Postes')" :description="__('Définissez les intitulés de poste rattachés à vos départements.')">
        <x-slot:actions>
            @can('organisation.manage')
                <x-primary-button type="button" x-data x-on:click="$dispatch('open-modal', 'position-create')">
                    {{ __('Nouveau poste') }}
                </x-primary-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form method="GET" action="{{ route('organisation.positions.index') }}" class="mb-6 max-w-xs">
        <x-text-input type="search" name="search" value="{{ $search }}" class="w-full"
            placeholder="Rechercher un poste..." />
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Intitulé
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Département
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Employés
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($positions as $position)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-fg">{{ $position->title }}</td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $position->department?->name ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $position->employees_count }}</td>
                        <td class="px-6 py-4 text-sm">
                            @if ($position->is_active)
                                <x-status-chip tone="success">Actif</x-status-chip>
                            @else
                                <x-status-chip tone="neutral">Inactif</x-status-chip>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm space-x-3">
                            @can('organisation.manage')
                                <button type="button" x-data
                                    x-on:click="$dispatch('open-modal', 'position-edit-{{ $position->id }}')"
                                    class="text-brand hover:underline">Modifier</button>

                                <form method="POST" action="{{ route('organisation.positions.destroy', $position) }}"
                                    class="inline" onsubmit="return confirm('Supprimer ce poste ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-danger hover:underline">Supprimer</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-muted">Aucun poste pour le moment.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $positions->links() }}
    </div>

    <x-modal name="position-create" :show="old('_modal') === 'position-create'" focusable>
        <form method="POST" action="{{ route('organisation.positions.store') }}" class="p-6">
            @csrf
            <input type="hidden" name="_modal" value="position-create">

            <h2 class="text-lg font-medium text-fg">{{ __('Nouveau poste') }}</h2>

            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <x-input-label for="create_title" value="Intitulé" />
                    <x-text-input name="title" id="create_title"
                        value="{{ old('_modal') === 'position-create' ? old('title') : '' }}" class="mt-1" />
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="create_code" value="Code" />
                    <x-text-input name="code" id="create_code"
                        value="{{ old('_modal') === 'position-create' ? old('code') : '' }}" class="mt-1" />
                    <x-input-error :messages="$errors->get('code')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="create_department_id" value="Département" />
                    <x-select name="department_id" id="create_department_id" class="mt-1">
                        <option value="">—</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}
                            </option>
                        @endforeach
                    </x-select>
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="create_description" value="Description" />
                    <textarea name="description" id="create_description" rows="3"
                        class="mt-1 block w-full rounded-lg border-line bg-surface text-sm text-fg shadow-sm focus:border-brand focus:ring-brand dark:focus:border-brand dark:focus:ring-brand">{{ old('description') }}</textarea>
                </div>

                <div class="flex items-center">
                    <input type="hidden" name="is_active" value="0">
                    <input name="is_active" id="create_is_active" type="checkbox" value="1" checked
                        class="rounded border-line text-brand shadow-sm focus:ring-brand">
                    <label for="create_is_active" class="ml-2 text-sm text-muted">Poste actif</label>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button"
                    x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                <x-primary-button class="ms-3">{{ __('Enregistrer') }}</x-primary-button>
            </div>
        </form>
    </x-modal>

    @foreach ($positions as $position)
        <x-modal name="position-edit-{{ $position->id }}" :show="old('_modal') === 'position-edit-'.$position->id" focusable>
            <form method="POST" action="{{ route('organisation.positions.update', $position) }}" class="p-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="_modal" value="position-edit-{{ $position->id }}">

                <h2 class="text-lg font-medium text-fg">{{ __('Modifier le poste') }}</h2>

                @php $isEditingThis = old('_modal') === 'position-edit-'.$position->id; @endphp

                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <x-input-label for="edit_title_{{ $position->id }}" value="Intitulé" />
                        <x-text-input name="title" id="edit_title_{{ $position->id }}"
                            value="{{ $isEditingThis ? old('title') : $position->title }}" class="mt-1" />
                        <x-input-error :messages="$isEditingThis ? $errors->get('title') : []" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="edit_code_{{ $position->id }}" value="Code" />
                        <x-text-input name="code" id="edit_code_{{ $position->id }}"
                            value="{{ $isEditingThis ? old('code') : $position->code }}" class="mt-1" />
                        <x-input-error :messages="$isEditingThis ? $errors->get('code') : []" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="edit_department_id_{{ $position->id }}" value="Département" />
                        <x-select name="department_id" id="edit_department_id_{{ $position->id }}" class="mt-1">
                            <option value="">—</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" @selected(($isEditingThis ? old('department_id') : $position->department_id) == $department->id)>
                                    {{ $department->name }}</option>
                            @endforeach
                        </x-select>
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="edit_description_{{ $position->id }}" value="Description" />
                        <textarea name="description" id="edit_description_{{ $position->id }}" rows="3"
                            class="mt-1 block w-full rounded-lg border-line bg-surface text-sm text-fg shadow-sm focus:border-brand focus:ring-brand dark:focus:border-brand dark:focus:ring-brand">{{ $isEditingThis ? old('description') : $position->description }}</textarea>
                    </div>

                    <div class="flex items-center">
                        <input type="hidden" name="is_active" value="0">
                        <input name="is_active" id="edit_is_active_{{ $position->id }}" type="checkbox"
                            value="1" @checked($isEditingThis ? old('is_active') : $position->is_active)
                            class="rounded border-line text-brand shadow-sm focus:ring-brand">
                        <label for="edit_is_active_{{ $position->id }}" class="ml-2 text-sm text-muted">Poste
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
