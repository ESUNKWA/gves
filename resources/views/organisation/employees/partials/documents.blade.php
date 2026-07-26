<h3 class="text-base font-semibold text-fg mb-4">Documents</h3>

@can('employees.manage')
    <form method="POST" action="{{ route('organisation.employees.documents.store', $employee) }}"
        enctype="multipart/form-data"
        class="rounded-xl border border-line-soft bg-surface shadow-card p-4 mb-4 grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
        @csrf
        <div>
            <x-input-label for="doc_title" value="Titre" />
            <x-text-input name="title" id="doc_title" value="{{ old('title') }}" class="mt-1" />
            <x-input-error :messages="$errors->get('title')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="doc_category" value="Catégorie" />
            <x-select name="category" id="doc_category" class="mt-1">
                @foreach ($documentCategories as $value => $label)
                    <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                @endforeach
            </x-select>
            <x-input-error :messages="$errors->get('category')" class="mt-2" />
        </div>

        <div class="sm:col-span-1">
            <x-input-label for="doc_file" value="Fichier" />
            <input name="file" id="doc_file" type="file" class="mt-1 block w-full text-sm text-muted">
            <x-input-error :messages="$errors->get('file')" class="mt-2" />
        </div>

        <div>
            <x-primary-button type="submit">{{ __('Téléverser') }}</x-primary-button>
        </div>
    </form>
@endcan

<div class="rounded-xl border border-line-soft bg-surface shadow-card overflow-hidden">
    <table class="min-w-full divide-y divide-line">
        <thead class="bg-surface-2">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">Titre</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">Catégorie</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">Ajouté le</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-line">
            @forelse ($documents as $document)
                <tr>
                    <td class="px-4 py-3 text-sm font-medium text-fg">{{ $document->title }}</td>
                    <td class="px-4 py-3 text-sm text-muted">
                        {{ $documentCategories[$document->category] ?? $document->category }}</td>
                    <td class="px-4 py-3 text-sm text-muted">{{ $document->uploaded_at?->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-right text-sm space-x-3">
                        <button type="button" x-data
                            x-on:click="$dispatch('open-modal', 'view-document-{{ $document->id }}')"
                            class="text-brand hover:underline">Voir</button>
                        <a href="{{ route('organisation.employees.documents.download', [$employee, $document]) }}"
                            class="text-brand hover:underline">Télécharger</a>
                        @can('employees.manage')
                            <form method="POST"
                                action="{{ route('organisation.employees.documents.destroy', [$employee, $document]) }}"
                                class="inline" onsubmit="return confirm('Supprimer ce document ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-danger hover:underline">Supprimer</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-4 py-6 text-center text-sm text-muted">Aucun document.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@foreach ($documents as $document)
    <x-modal name="view-document-{{ $document->id }}" maxWidth="4xl">
        <div class="p-4">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-base font-medium text-fg">{{ $document->title }}</h2>
                <button type="button" x-on:click="$dispatch('close')" class="text-muted hover:text-fg">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <iframe
                :src="show ? '{{ route('organisation.employees.documents.view', [$employee, $document]) }}#toolbar=0&navpanes=0' : ''"
                class="w-full rounded-lg border border-line-soft bg-surface-2" style="height: 75vh;"></iframe>
        </div>
    </x-modal>
@endforeach
