<x-app-layout>
    <x-page-header :title="__('Mes documents')" :description="__('Vos documents RH (contrat, attestations, etc.).')">
        <x-slot:actions>
            <x-primary-button type="button" x-data
                x-on:click="$dispatch('open-modal', 'document-request-create')">{{ __('Demander un document') }}</x-primary-button>
        </x-slot:actions>
    </x-page-header>

    <x-portal-tabs />

    @if ($myDocumentRequests->isNotEmpty())
        <div class="mb-8">
            <h3 class="text-base font-semibold text-fg">Mes demandes de documents</h3>
            <p class="mb-3 text-xs text-muted">Le suivi de vos demandes envoyées aux RH — une fois traitées, le
                document apparaît ci-dessous dans « Mes documents ».</p>
            <x-data-table>
                <table class="min-w-full divide-y divide-line">
                    <thead class="bg-surface-2">
                        <tr>
                            <th data-sort
                                class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                                Gabarit</th>
                            <th data-sort
                                class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                                Demandée le</th>
                            <th data-sort
                                class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">
                                Statut</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($myDocumentRequests as $documentRequest)
                            @php
                                $tones = [
                                    'pending' => 'warning',
                                    'fulfilled' => 'success',
                                    'rejected' => 'danger',
                                    'cancelled' => 'neutral',
                                ];
                            @endphp
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-fg">
                                    {{ $documentRequest->template?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-muted"
                                    data-sort-value="{{ $documentRequest->created_at->timestamp }}">
                                    {{ $documentRequest->created_at->format('d/m/Y à H:i') }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <x-status-chip :tone="$tones[$documentRequest->status] ?? 'neutral'">
                                        {{ $documentRequestStatuses[$documentRequest->status] ?? $documentRequest->status }}
                                    </x-status-chip>
                                    @if ($documentRequest->status === 'rejected' && $documentRequest->decision_note)
                                        <p class="mt-1 text-xs text-muted italic">{{ $documentRequest->decision_note }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-sm">
                                    @if ($documentRequest->status === 'pending')
                                        <form method="POST"
                                            action="{{ route('portal.document-requests.destroy', $documentRequest) }}"
                                            class="inline" data-confirm="Annuler cette demande ?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-danger hover:underline">Annuler</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-data-table>
        </div>
    @endif

    @if ($pendingSignatures->isNotEmpty())
        <div class="mb-6 rounded-xl border border-warning-soft bg-warning-soft/40 p-4">
            <h3 class="text-sm font-semibold text-fg mb-3">Documents à signer</h3>
            <ul class="space-y-2">
                @foreach ($pendingSignatures as $pendingSignature)
                    <li class="flex items-center justify-between rounded-lg bg-surface px-4 py-3 shadow-card">
                        <span class="text-sm text-fg">{{ $pendingSignature->title }}</span>
                        <a href="{{ route('portal.document-requests.show', $pendingSignature) }}">
                            <x-primary-button type="button">{{ __('Consulter et signer') }}</x-primary-button>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <h3 class="text-base font-semibold text-fg">Mes documents</h3>
    <p class="mb-3 text-xs text-muted">Les documents déjà disponibles dans votre dossier — à consulter ou
        télécharger.</p>
    <x-data-table>
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-surface-2">
                <tr>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Titre</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Catégorie</th>
                    <th data-sort class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">
                        Ajouté le</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($documents as $document)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-fg">{{ $document->title }}</td>
                        <td class="px-6 py-4 text-sm text-muted">
                            {{ $categories[$document->category] ?? $document->category }}
                        </td>
                        <td class="px-6 py-4 text-sm text-muted"
                            data-sort-value="{{ $document->uploaded_at?->timestamp ?? 0 }}">
                            {{ $document->uploaded_at?->format('d/m/Y à H:i') }}</td>
                        <td class="px-6 py-4 text-right text-sm space-x-3">
                            <button type="button" x-data
                                x-on:click="$dispatch('open-modal', 'view-document-{{ $document->id }}')"
                                class="text-brand hover:underline">Voir</button>
                            <a href="{{ route('portal.documents.download', $document) }}"
                                class="text-brand hover:underline">Télécharger</a>
                        </td>
                    </tr>
                @empty
                    <tr data-empty-row>
                        <td colspan="4" class="px-6 py-8 text-center text-sm text-muted">Aucun document pour le
                            moment.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-data-table>

    @foreach ($documents as $document)
        <x-modal name="view-document-{{ $document->id }}" maxWidth="4xl">
            <div class="p-4">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-base font-medium text-fg">{{ $document->title }}</h2>
                    <button type="button" x-on:click="$dispatch('close')" class="text-muted hover:text-fg">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <iframe :src="show ? '{{ route('portal.documents.view', $document) }}#toolbar=0&navpanes=0' : ''"
                    class="w-full rounded-lg border border-line-soft bg-surface-2" style="height: 75vh;"></iframe>
            </div>
        </x-modal>
    @endforeach

    <x-modal name="document-request-create" :show="$errors->has('document_template_id') || $errors->has('reason') || $errors->has('field_values.*')" focusable>
        <form method="POST" action="{{ route('portal.document-requests.store') }}" class="p-6"
            x-data="{
                templateId: '{{ old('document_template_id') }}',
                templatesFields: {{ Illuminate\Support\Js::from($documentTemplates->mapWithKeys(fn($t) => [(string) $t->id => $t->fields ?? []])) }},
                get selectedFields() { return this.templatesFields[this.templateId] ?? []; },
            }">
            @csrf

            <h2 class="text-lg font-medium text-fg">{{ __('Demander un document') }}</h2>
            <p class="mt-1 text-sm text-muted">Votre demande sera envoyée à RH, qui générera le document pour
                signature.</p>

            <div class="mt-6 grid grid-cols-1 gap-4">
                <div>
                    <x-input-label for="document_request_template" value="Type de document" />
                    <x-select name="document_template_id" id="document_request_template" x-model="templateId"
                        class="mt-1">
                        <option value=""></option>
                        @foreach ($documentTemplates as $documentTemplate)
                            <option value="{{ $documentTemplate->id }}">{{ $documentTemplate->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('document_template_id')" class="mt-2" />
                </div>

                <template x-for="field in selectedFields" :key="field.key">
                    <div>
                        <label class="block text-sm font-medium text-fg"
                            x-text="field.label + (field.required ? ' *' : '')"></label>
                        <template x-if="field.type === 'textarea'">
                            <textarea :name="`field_values[${field.key}]`" :required="field.required" rows="3"
                                class="mt-1 block w-full rounded-lg border-line bg-surface text-sm text-fg shadow-sm focus:border-brand focus:ring-brand"></textarea>
                        </template>
                        <template x-if="field.type !== 'textarea'">
                            <input :type="field.type === 'number' ? 'number' : 'text'"
                                :name="`field_values[${field.key}]`" :required="field.required"
                                class="mt-1 block w-full rounded-lg border-line bg-surface text-sm text-fg shadow-sm focus:border-brand focus:ring-brand">
                        </template>
                    </div>
                </template>

                @if ($errors->has('field_values.*'))
                    <x-input-error :messages="$errors->get('field_values.*')" />
                @endif

                <div>
                    <x-input-label for="document_request_reason" value="Motif (optionnel)" />
                    <textarea name="reason" id="document_request_reason" rows="3"
                        class="mt-1 block w-full rounded-lg border-line bg-surface text-sm text-fg shadow-sm focus:border-brand focus:ring-brand">{{ old('reason') }}</textarea>
                    <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button"
                    x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                <x-primary-button class="ms-3">{{ __('Envoyer la demande') }}</x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
