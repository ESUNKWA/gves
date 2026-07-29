@php
    $generatedDocument = $approval->generatedDocument;
@endphp

<x-app-layout>
    <x-page-header :title="$generatedDocument->title" :description="$approval->label()">
        <x-slot:actions>
            <a href="{{ route('approvals.index') }}" class="text-sm text-muted hover:text-fg">
                &larr; {{ __('Retour à mes validations') }}
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-xl border border-line-soft bg-surface shadow-card p-6">
                <h2 class="text-base font-semibold text-fg">{{ __('Demande') }}</h2>
                <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-muted">{{ __('Employé') }}</dt>
                        <dd class="mt-1 text-sm text-fg">
                            <a href="{{ route('organisation.employees.show', $generatedDocument->employee) }}"
                                class="hover:underline">{{ $generatedDocument->employee->full_name }}</a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-muted">{{ __('Gabarit') }}</dt>
                        <dd class="mt-1 text-sm text-fg">{{ $generatedDocument->template?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-muted">{{ __('Demandé le') }}
                        </dt>
                        <dd class="mt-1 text-sm text-fg">{{ $generatedDocument->created_at->format('d/m/Y à H:i') }}
                        </dd>
                    </div>
                </dl>
            </div>

            @if ($generatedDocument->approvals->where('status', '!=', 'pending')->isNotEmpty())
                <div class="rounded-xl border border-line-soft bg-surface shadow-card p-6">
                    <h2 class="text-base font-semibold text-fg">{{ __('Étapes précédentes') }}</h2>
                    <ol class="mt-4 space-y-4">
                        @foreach ($generatedDocument->approvals->where('status', '!=', 'pending') as $step)
                            <li class="flex items-start gap-3">
                                <span
                                    class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full {{ $step->status === 'approved' ? 'bg-success-soft text-success' : 'bg-danger-soft text-danger' }}">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                        stroke="currentColor">
                                        @if ($step->status === 'approved')
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M4.5 12.75l6 6 9-13.5" />
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6 18 18 6M6 6l12 12" />
                                        @endif
                                    </svg>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-fg">{{ $step->label() }}</p>
                                    <p class="text-xs text-muted">
                                        {{ $step->status === 'approved' ? 'Approuvée' : 'Refusée' }} par
                                        {{ $step->decidedBy?->name ?? '—' }}
                                        le {{ $step->decided_at?->format('d/m/Y à H:i') }}
                                    </p>
                                    @if ($step->note)
                                        <p class="mt-1 text-sm italic text-muted">{{ $step->note }}</p>
                                    @endif
                                    @if ($step->signature_data)
                                        <img src="{{ $step->signature_data }}" alt="Signature"
                                            class="mt-2 h-16 rounded border border-line-soft bg-white">
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif

            <div class="rounded-xl border border-line-soft bg-surface shadow-card p-6">
                <h2 class="text-base font-semibold text-fg">{{ __('Contenu du document') }}</h2>
                <div class="document-content prose prose-sm mt-4 max-w-none rounded-lg border border-line-soft bg-surface-2 p-6 text-fg"
                    style="max-height: 60vh; overflow-y: auto;">
                    @include('pdf.partials.header', ['company' => \App\Models\CompanySetting::current()])
                    {!! $generatedDocument->content !!}
                </div>
            </div>
        </div>

        <div class="lg:col-span-1">
            <div class="sticky top-6 space-y-4 rounded-xl border border-line-soft bg-surface shadow-card p-6"
                x-data="{
                    drawing: false,
                    hasDrawn: false,
                    ctx: null,
                    init() {
                        const canvas = this.$refs.canvas;
                        const ratio = window.devicePixelRatio || 1;
                        canvas.width = canvas.offsetWidth * ratio;
                        canvas.height = canvas.offsetHeight * ratio;
                        this.ctx = canvas.getContext('2d');
                        this.ctx.scale(ratio, ratio);
                        this.ctx.lineWidth = 2;
                        this.ctx.lineCap = 'round';
                        this.ctx.strokeStyle = '#111827';
                    },
                    pos(e) {
                        const rect = this.$refs.canvas.getBoundingClientRect();
                        return { x: e.clientX - rect.left, y: e.clientY - rect.top };
                    },
                    start(e) {
                        this.drawing = true;
                        this.hasDrawn = true;
                        const p = this.pos(e);
                        this.ctx.beginPath();
                        this.ctx.moveTo(p.x, p.y);
                    },
                    draw(e) {
                        if (!this.drawing) return;
                        const p = this.pos(e);
                        this.ctx.lineTo(p.x, p.y);
                        this.ctx.stroke();
                    },
                    stop() { this.drawing = false; },
                    clear() {
                        this.ctx.clearRect(0, 0, this.$refs.canvas.width, this.$refs.canvas.height);
                        this.hasDrawn = false;
                    },
                }" x-init="init()">
                <h2 class="text-base font-semibold text-fg">{{ __('Ma décision') }}</h2>
                <p class="text-sm text-muted">
                    {{ __('Vous pouvez approuver directement, ou signer avant d\'approuver.') }}</p>

                <div>
                    <canvas x-ref="canvas" class="w-full touch-none rounded-lg border border-line-soft bg-white"
                        style="height: 140px;" x-on:pointerdown="start($event)" x-on:pointermove="draw($event)"
                        x-on:pointerup="stop()" x-on:pointerleave="stop()"></canvas>
                    <button type="button" x-on:click="clear()" class="mt-1 text-xs text-muted hover:text-fg">
                        {{ __('Effacer la signature') }}
                    </button>
                </div>

                <form method="POST" action="{{ route('approvals.approve', $approval) }}">
                    @csrf
                    <input type="hidden" name="signature_data" x-ref="signatureInput">

                    <div class="space-y-2">
                        <x-primary-button type="submit" x-on:click="$refs.signatureInput.value = ''"
                            class="w-full justify-center">
                            {{ __('Approuver') }}
                        </x-primary-button>
                        <x-secondary-button type="submit"
                            x-on:click="if (!hasDrawn) { $event.preventDefault(); alert('Dessinez une signature dans le cadre ci-dessus, ou utilisez « Approuver ».'); return; } $refs.signatureInput.value = $refs.canvas.toDataURL('image/png');"
                            class="w-full justify-center">
                            {{ __('Signer et approuver') }}
                        </x-secondary-button>
                    </div>
                </form>

                <button type="button" x-data
                    x-on:click="$dispatch('open-modal', 'reject-approval-{{ $approval->id }}')"
                    class="w-full text-center text-sm text-danger hover:underline">
                    {{ __('Refuser cette demande') }}
                </button>
            </div>
        </div>
    </div>

    <x-modal name="reject-approval-{{ $approval->id }}" focusable>
        <form method="POST" action="{{ route('approvals.reject', $approval) }}" class="p-6">
            @csrf
            <h2 class="text-lg font-medium text-fg">{{ __('Refuser la demande') }}</h2>
            <p class="mt-1 text-sm text-muted">{{ $generatedDocument->employee->full_name }} —
                {{ $generatedDocument->title }}</p>

            <div class="mt-4">
                <x-input-label for="reject_note_{{ $approval->id }}" value="Motif du refus" />
                <textarea name="note" id="reject_note_{{ $approval->id }}" rows="3" required
                    class="mt-1 block w-full rounded-lg border-line bg-surface text-sm text-fg shadow-sm focus:border-brand focus:ring-brand"></textarea>
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button"
                    x-on:click="$dispatch('close')">{{ __('Annuler') }}</x-secondary-button>
                <x-danger-button class="ms-3">{{ __('Refuser') }}</x-danger-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
