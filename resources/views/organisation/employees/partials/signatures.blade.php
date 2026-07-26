@php $isGenerating = old('_modal') === 'signature-generate'; @endphp

<div class="flex items-center justify-between mb-4">
    <h3 class="text-base font-semibold text-fg">Documents & signatures</h3>
    @can('documents.manage')
        <x-secondary-button type="button" x-data
            x-on:click="$dispatch('open-modal', 'signature-generate')">{{ __('Générer un document') }}</x-secondary-button>
    @endcan
</div>

<div class="rounded-xl border border-line-soft bg-surface shadow-card overflow-hidden">
    <table class="min-w-full divide-y divide-line">
        <thead class="bg-surface-2">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">Titre</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">Généré le</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">Signé le</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-muted uppercase tracking-wider">Statut</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-line">
            @forelse ($generatedDocuments as $generatedDocument)
                @php
                    $tones = ['pending' => 'warning', 'signed' => 'success', 'cancelled' => 'neutral'];
                @endphp
                <tr>
                    <td class="px-4 py-3 text-sm font-medium text-fg">{{ $generatedDocument->title }}</td>
                    <td class="px-4 py-3 text-sm text-muted">{{ $generatedDocument->created_at->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-sm text-muted">
                        {{ $generatedDocument->signed_at?->format('d/m/Y à H:i') ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm">
                        <x-status-chip :tone="$tones[$generatedDocument->status] ?? 'neutral'">
                            {{ $signatureStatuses[$generatedDocument->status] ?? $generatedDocument->status }}
                        </x-status-chip>
                    </td>
                    <td class="px-4 py-3 text-right text-sm space-x-3">
                        <button type="button" x-data
                            x-on:click="$dispatch('open-modal', 'view-generated-{{ $generatedDocument->id }}')"
                            class="text-brand hover:underline">Aperçu</button>

                        @if ($generatedDocument->status === 'signed' && $generatedDocument->employee_document_id)
                            <a href="{{ route('organisation.employees.documents.view', [$employee, $generatedDocument->employee_document_id]) }}"
                                target="_blank" rel="noopener" class="text-brand hover:underline">Voir le PDF signé</a>
                        @endif

                        @can('documents.manage')
                            @if ($generatedDocument->status === 'pending')
                                <form method="POST"
                                    action="{{ route('organisation.employees.document-requests.destroy', [$employee, $generatedDocument]) }}"
                                    class="inline" onsubmit="return confirm('Annuler ce document ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-danger hover:underline">Annuler</button>
                                </form>
                            @endif
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-sm text-muted">Aucun document généré pour cet
                        employé.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@can('documents.manage')
    <x-modal name="signature-generate" :show="$isGenerating" focusable>
        <div x-data="{
            templateId: '{{ old('document_template_id', $documentTemplates->first()?->id) }}',
            title: '{{ old('title', '') }}',
            names: {{ $documentTemplates->pluck('name', 'id')->toJson() }},
        }" x-init="if (!title && names[templateId]) title = names[templateId] + ' — {{ $employee->full_name }}'">
            <form method="POST" action="{{ route('organisation.employees.document-requests.store', $employee) }}"
                class="p-6">
                @csrf
                <input type="hidden" name="_modal" value="signature-generate">

                <h2 class="text-lg font-medium text-fg">{{ __('Générer un document') }}</h2>
                <p class="mt-1 text-sm text-muted">Le document sera généré à partir du gabarit et envoyé à l'employé
                    pour signature dans Mon espace.</p>

                <div class="mt-6 grid grid-cols-1 gap-4">
                    <div>
                        <x-input-label for="document_template_id" value="Gabarit" />
                        <x-select name="document_template_id" id="document_template_id" x-model="templateId"
                            x-on:change="title = names[templateId] + ' — {{ $employee->full_name }}'" class="mt-1">
                            @foreach ($documentTemplates as $documentTemplate)
                                <option value="{{ $documentTemplate->id }}">{{ $documentTemplate->name }}</option>
                            @endforeach
                        </x-select>
                        <x-input-error :messages="$isGenerating ? $errors->get('document_template_id') : []" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="signature_title" value="Titre du document" />
                        <x-text-input name="title" id="signature_title" x-model="title" class="mt-1" />
                        <x-input-error :messages="$isGenerating ? $errors->get('title') : []" class="mt-2" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <x-secondary-button type="button"
                        x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                    <x-primary-button class="ms-3">{{ __('Générer') }}</x-primary-button>
                </div>
            </form>
        </div>
    </x-modal>
@endcan

@foreach ($generatedDocuments as $generatedDocument)
    <x-modal name="view-generated-{{ $generatedDocument->id }}" maxWidth="4xl">
        <div class="p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-medium text-fg">{{ $generatedDocument->title }}</h2>
                <button type="button" x-on:click="$dispatch('close')" class="text-muted hover:text-fg">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="prose prose-sm max-w-none rounded-lg border border-line-soft bg-surface-2 p-6 text-fg"
                style="max-height: 65vh; overflow-y: auto;">
                @include('pdf.partials.header')
                {!! $generatedDocument->content !!}
            </div>
            @if ($generatedDocument->status === 'signed')
                <p class="mt-3 text-xs text-muted">Signé le
                    {{ $generatedDocument->signed_at?->format('d/m/Y à H:i') }} — empreinte
                    {{ Str::limit($generatedDocument->document_hash, 16, '…') }}</p>
            @endif
        </div>
    </x-modal>
@endforeach
