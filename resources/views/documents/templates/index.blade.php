<x-app-layout>
    <x-page-header :title="__('Gabarits de documents')" :description="__('Modèles réutilisables pour générer des documents à faire signer aux employés.')">
        <x-slot:actions>
            <x-primary-button type="button" x-data x-on:click="$dispatch('open-modal', 'template-create')">
                {{ __('Nouveau gabarit') }}
            </x-primary-button>
        </x-slot:actions>
    </x-page-header>

    <x-data-table>
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-surface-2">
                <tr>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Nom</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Catégorie</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Utilisé</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Statut</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($templates as $template)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-fg">{{ $template->name }}</td>
                        <td class="px-6 py-4 text-sm text-muted">
                            {{ $categories[$template->category] ?? $template->category }}</td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $template->generated_documents_count }} document(s)
                        </td>
                        <td class="px-6 py-4 text-sm">
                            @if ($template->is_active)
                                <x-status-chip tone="success">Actif</x-status-chip>
                            @else
                                <x-status-chip tone="neutral">Inactif</x-status-chip>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm space-x-3">
                            <button type="button" x-data
                                x-on:click="$dispatch('open-modal', 'template-edit-{{ $template->id }}')"
                                class="text-brand hover:underline">Modifier</button>

                            @if ($template->generated_documents_count === 0)
                                <form method="POST" action="{{ route('documents.templates.destroy', $template) }}"
                                    class="inline" data-confirm="Supprimer ce gabarit ?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-danger hover:underline">Supprimer</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr data-empty-row>
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-muted">Aucun gabarit pour le
                            moment.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-data-table>

    <x-modal name="template-create" :show="old('_modal') === 'template-create'" focusable maxWidth="4xl">
        <form method="POST" action="{{ route('documents.templates.store') }}" class="p-6">
            @csrf
            <input type="hidden" name="_modal" value="template-create">

            <h2 class="text-lg font-medium text-fg">{{ __('Nouveau gabarit') }}</h2>

            @include('documents.templates.partials.fields', [
                'id' => 'create',
                'documentTemplate' => null,
                'showErrors' => old('_modal') === 'template-create',
            ])

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button"
                    x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                <x-primary-button class="ms-3">{{ __('Enregistrer') }}</x-primary-button>
            </div>
        </form>
    </x-modal>

    @foreach ($templates as $template)
        @php $isEditingThis = old('_modal') === 'template-edit-'.$template->id; @endphp
        <x-modal name="template-edit-{{ $template->id }}" :show="$isEditingThis" focusable maxWidth="4xl">
            <form method="POST" action="{{ route('documents.templates.update', $template) }}" class="p-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="_modal" value="template-edit-{{ $template->id }}">

                <h2 class="text-lg font-medium text-fg">{{ __('Modifier le gabarit') }}</h2>

                @include('documents.templates.partials.fields', [
                    'id' => 'edit-' . $template->id,
                    'documentTemplate' => $template,
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
