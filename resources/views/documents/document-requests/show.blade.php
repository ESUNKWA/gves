<x-app-layout>
    @php
        $tones = ['pending' => 'warning', 'fulfilled' => 'success', 'rejected' => 'danger', 'cancelled' => 'neutral'];
    @endphp

    <x-page-header :title="__('Demande de document')" :description="$documentRequest->employee->full_name">
        <x-slot:actions>
            <a href="{{ route('documents.document-requests.index') }}" class="text-sm text-muted hover:text-fg">
                &larr; {{ __('Retour à la liste') }}
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-xl border border-line-soft bg-surface shadow-card p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-fg">{{ __('Détails de la demande') }}</h2>
                    <x-status-chip :tone="$tones[$documentRequest->status] ?? 'neutral'">
                        {{ $statuses[$documentRequest->status] ?? $documentRequest->status }}
                    </x-status-chip>
                </div>

                <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-muted">{{ __('Employé') }}</dt>
                        <dd class="mt-1 text-sm text-fg">
                            <a href="{{ route('organisation.employees.show', $documentRequest->employee) }}"
                                class="hover:underline">{{ $documentRequest->employee->full_name }}</a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-muted">{{ __('Gabarit demandé') }}
                        </dt>
                        <dd class="mt-1 text-sm text-fg">{{ $documentRequest->template?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-muted">{{ __('Demandée le') }}
                        </dt>
                        <dd class="mt-1 text-sm text-fg">{{ $documentRequest->created_at->format('d/m/Y à H:i') }}</dd>
                    </div>
                    @if ($documentRequest->reason)
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium uppercase tracking-wider text-muted">{{ __('Motif') }}
                            </dt>
                            <dd class="mt-1 text-sm text-fg">{{ $documentRequest->reason }}</dd>
                        </div>
                    @endif
                </dl>

                @if (!empty($documentRequest->field_values))
                    <div class="mt-6 border-t border-line-soft pt-4">
                        <h3 class="text-xs font-medium uppercase tracking-wider text-muted">
                            {{ __('Informations saisies par l\'employé') }}</h3>
                        <dl class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            @foreach ($documentRequest->field_values as $key => $value)
                                @php
                                    $fieldLabel =
                                        collect($documentRequest->template?->fields ?? [])->firstWhere('key', $key)[
                                            'label'
                                        ] ?? $key;
                                @endphp
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wider text-muted">
                                        {{ $fieldLabel }}</dt>
                                    <dd class="mt-1 text-sm font-medium text-fg">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif
            </div>

            @if ($preview)
                <div class="rounded-xl border border-line-soft bg-surface shadow-card p-6">
                    <h2 class="text-base font-semibold text-fg">{{ __('Aperçu du document qui sera généré') }}</h2>
                    <p class="mt-1 text-xs text-muted">
                        {{ __('Les variables ont été remplacées par les informations réelles de l\'employé et de la demande.') }}
                    </p>
                    <div class="document-content prose prose-sm mt-4 max-w-none rounded-lg border border-line-soft bg-surface-2 p-6 text-fg"
                        style="max-height: 60vh; overflow-y: auto;">
                        {!! $preview !!}
                    </div>
                </div>
            @endif

            @if ($documentRequest->status !== 'pending')
                <div class="rounded-xl border border-line-soft bg-surface shadow-card p-6">
                    <h2 class="text-base font-semibold text-fg">{{ __('Décision') }}</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wider text-muted">
                                {{ __('Traitée par') }}</dt>
                            <dd class="mt-1 text-sm text-fg">{{ $documentRequest->decidedBy?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wider text-muted">{{ __('Traitée le') }}
                            </dt>
                            <dd class="mt-1 text-sm text-fg">
                                {{ $documentRequest->decided_at?->format('d/m/Y à H:i') ?? '—' }}</dd>
                        </div>
                        @if ($documentRequest->decision_note)
                            <div class="sm:col-span-2">
                                <dt class="text-xs font-medium uppercase tracking-wider text-muted">
                                    {{ __('Motif du refus') }}</dt>
                                <dd class="mt-1 text-sm text-fg">{{ $documentRequest->decision_note }}</dd>
                            </div>
                        @endif
                        @if ($documentRequest->status === 'fulfilled' && $documentRequest->generatedDocument)
                            <div class="sm:col-span-2">
                                <a href="{{ route('organisation.employees.show', $documentRequest->employee) }}"
                                    class="text-sm text-brand hover:underline">
                                    {{ __('Voir le document généré sur la fiche employé') }} &rarr;
                                </a>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif
        </div>

        @if ($documentRequest->status === 'pending')
            <div class="lg:col-span-1">
                <div class="sticky top-6 rounded-xl border border-line-soft bg-surface shadow-card p-6">
                    <h2 class="text-base font-semibold text-fg">{{ __('Décision') }}</h2>
                    <p class="mt-1 text-sm text-muted">
                        {{ __('Vérifiez les informations ci-contre avant de valider ou refuser cette demande.') }}
                    </p>

                    <form method="POST" action="{{ route('documents.document-requests.approve', $documentRequest) }}"
                        class="mt-4" data-confirm="Générer ce document pour signature ?">
                        @csrf
                        <x-primary-button type="submit" class="w-full justify-center">
                            {{ __('Approuver et générer le document') }}
                        </x-primary-button>
                    </form>

                    <button type="button" x-data
                        x-on:click="$dispatch('open-modal', 'reject-doc-request-{{ $documentRequest->id }}')"
                        class="mt-3 w-full text-center text-sm text-danger hover:underline">
                        {{ __('Refuser cette demande') }}
                    </button>
                </div>
            </div>

            <x-modal name="reject-doc-request-{{ $documentRequest->id }}" focusable>
                <form method="POST" action="{{ route('documents.document-requests.reject', $documentRequest) }}"
                    class="p-6">
                    @csrf
                    <h2 class="text-lg font-medium text-fg">{{ __('Refuser la demande') }}</h2>
                    <p class="mt-1 text-sm text-muted">
                        {{ $documentRequest->employee->full_name }} — {{ $documentRequest->template?->name ?? '—' }}
                    </p>

                    <div class="mt-4">
                        <x-input-label for="reject_note_{{ $documentRequest->id }}" value="Motif du refus" />
                        <textarea name="decision_note" id="reject_note_{{ $documentRequest->id }}" rows="3" required
                            class="mt-1 block w-full rounded-lg border-line bg-surface text-sm text-fg shadow-sm focus:border-brand focus:ring-brand"></textarea>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <x-secondary-button type="button"
                            x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                        <x-danger-button class="ms-3">{{ __('Refuser') }}</x-danger-button>
                    </div>
                </form>
            </x-modal>
        @endif
    </div>
</x-app-layout>
