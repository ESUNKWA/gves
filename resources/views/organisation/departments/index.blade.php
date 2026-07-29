<x-app-layout>
    <x-page-header :title="__('Départements')" :description="__('Structurez votre organisation en départements et sous-équipes.')">
        <x-slot:actions>
            @can('organisation.manage')
                <x-primary-button type="button" x-data x-on:click="$dispatch('open-modal', 'department-create')">
                    {{ __('Nouveau département') }}
                </x-primary-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-data-table>
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-surface-2">
                <tr>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Nom</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Site</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Rattaché à</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Responsable</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Employés</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($departments as $department)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-fg">{{ $department->name }} <span
                                class="text-faint">({{ $department->code }})</span></td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $department->site?->name ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $department->parent?->name ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $department->manager?->full_name ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $department->employees_count }}</td>
                        <td class="px-6 py-4 text-right text-sm space-x-3">
                            @can('organisation.manage')
                                <button type="button" x-data
                                    x-on:click="$dispatch('open-modal', 'department-edit-{{ $department->id }}')"
                                    class="text-brand hover:underline">Modifier</button>

                                <form method="POST" action="{{ route('organisation.departments.destroy', $department) }}"
                                    class="inline" data-confirm="Supprimer ce département ?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-danger hover:underline">Supprimer</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr data-empty-row>
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-muted">Aucun département pour le
                            moment.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-data-table>

    <x-modal name="department-create" :show="old('_modal') === 'department-create'" focusable>
        <form method="POST" action="{{ route('organisation.departments.store') }}" class="p-6">
            @csrf
            <input type="hidden" name="_modal" value="department-create">

            <h2 class="text-lg font-medium text-fg">{{ __('Nouveau département') }}</h2>

            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <x-input-label for="create_name" value="Nom" />
                    <x-text-input name="name" id="create_name"
                        value="{{ old('_modal') === 'department-create' ? old('name') : '' }}" class="mt-1" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="create_code" value="Code" />
                    <x-text-input name="code" id="create_code"
                        value="{{ old('_modal') === 'department-create' ? old('code') : '' }}" class="mt-1" />
                    <x-input-error :messages="$errors->get('code')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="create_site_id" value="Site" />
                    <x-select name="site_id" id="create_site_id" class="mt-1">
                        <option value="">—</option>
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}" @selected(old('site_id') == $site->id)>{{ $site->name }}
                            </option>
                        @endforeach
                    </x-select>
                </div>

                <div>
                    <x-input-label for="create_parent_id" value="Rattaché à" />
                    <x-select name="parent_id" id="create_parent_id" class="mt-1">
                        <option value="">—</option>
                        @foreach ($allDepartments as $option)
                            <option value="{{ $option->id }}" @selected(old('parent_id') == $option->id)>{{ $option->name }}
                            </option>
                        @endforeach
                    </x-select>
                </div>

                <div>
                    <x-input-label for="create_manager_id" value="Responsable" />
                    <x-select name="manager_id" id="create_manager_id" class="mt-1">
                        <option value="">—</option>
                        @foreach ($managers as $manager)
                            <option value="{{ $manager->id }}" @selected(old('manager_id') == $manager->id)>{{ $manager->full_name }}
                            </option>
                        @endforeach
                    </x-select>
                </div>

                <div class="flex items-center">
                    <input type="hidden" name="is_active" value="0">
                    <input name="is_active" id="create_is_active" type="checkbox" value="1" checked
                        class="rounded border-line text-brand shadow-sm focus:ring-brand">
                    <label for="create_is_active" class="ml-2 text-sm text-muted">Département actif</label>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button"
                    x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                <x-primary-button class="ms-3">{{ __('Enregistrer') }}</x-primary-button>
            </div>
        </form>
    </x-modal>

    @foreach ($departments as $department)
        <x-modal name="department-edit-{{ $department->id }}" :show="old('_modal') === 'department-edit-'.$department->id" focusable>
            <form method="POST" action="{{ route('organisation.departments.update', $department) }}" class="p-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="_modal" value="department-edit-{{ $department->id }}">

                <h2 class="text-lg font-medium text-fg">{{ __('Modifier le département') }}</h2>

                @php $isEditingThis = old('_modal') === 'department-edit-'.$department->id; @endphp

                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <x-input-label for="edit_name_{{ $department->id }}" value="Nom" />
                        <x-text-input name="name" id="edit_name_{{ $department->id }}"
                            value="{{ $isEditingThis ? old('name') : $department->name }}" class="mt-1" />
                        <x-input-error :messages="$isEditingThis ? $errors->get('name') : []" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="edit_code_{{ $department->id }}" value="Code" />
                        <x-text-input name="code" id="edit_code_{{ $department->id }}"
                            value="{{ $isEditingThis ? old('code') : $department->code }}" class="mt-1" />
                        <x-input-error :messages="$isEditingThis ? $errors->get('code') : []" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="edit_site_id_{{ $department->id }}" value="Site" />
                        <x-select name="site_id" id="edit_site_id_{{ $department->id }}" class="mt-1">
                            <option value="">—</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site->id }}" @selected(($isEditingThis ? old('site_id') : $department->site_id) == $site->id)>{{ $site->name }}
                                </option>
                            @endforeach
                        </x-select>
                    </div>

                    <div>
                        <x-input-label for="edit_parent_id_{{ $department->id }}" value="Rattaché à" />
                        <x-select name="parent_id" id="edit_parent_id_{{ $department->id }}" class="mt-1">
                            <option value="">—</option>
                            @foreach ($allDepartments->where('id', '!=', $department->id) as $option)
<option value="{{ $option->id }}" @selected(($isEditingThis ? old('parent_id') : $department->parent_id) == $option->id)>{{ $option->name }}</option>
@endforeach
 </x-select>
 <x-input-error :messages="$isEditingThis ? $errors->get('parent_id') : []" class="mt-2" />
 </div>

 <div>
 <x-input-label for="edit_manager_id_{{ $department->id }}" value="Responsable" />
 <x-select name="manager_id" id="edit_manager_id_{{ $department->id }}" class="mt-1">
 <option value="">—</option>
 @foreach ($managers as $manager)
<option value="{{ $manager->id }}" @selected(($isEditingThis ? old('manager_id') : $department->manager_id) == $manager->id)>{{ $manager->full_name }}</option>
 @endforeach
                                </x-select>
                                </div>

                                <div class="flex items-center">
                                <input type="hidden" name="is_active" value="0">
                                <input name="is_active" id="edit_is_active_{{ $department->id }}" type="checkbox"
                                value="1" @checked($isEditingThis ? old('is_active') : $department->is_active)
                                class="rounded border-line text-brand shadow-sm focus:ring-brand">
                                <label for="edit_is_active_{{ $department->id }}"
                                class="ml-2 text-sm text-muted">Département actif</label>
                                </div>
                                </div>

                                <div class="mt-6 flex justify-end">
                                <x-secondary-button type="button"
                                x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                                <x-primary-button
                                class="ms-3">{{ __('Enregistrer') }}</x-primary-button>
                                </div>
                                </form>
                                </x-modal>
                            @endforeach
                            </x-app-layout>)
