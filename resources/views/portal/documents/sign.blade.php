<x-app-layout>
    <x-page-header :title="$generatedDocument->title" :description="__('Merci de lire attentivement le document avant de le signer.')">
        <x-slot:actions>
            <a href="{{ route('portal.documents.index') }}">
                <x-secondary-button type="button">{{ __('Retour') }}</x-secondary-button>
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="prose prose-sm max-w-none rounded-xl border border-line-soft bg-surface shadow-card p-6 text-fg mb-6"
        style="max-height: 50vh; overflow-y: auto;">
        @include('pdf.partials.header')
        {!! $generatedDocument->content !!}
    </div>

    <div class="rounded-xl border border-line-soft bg-surface shadow-card p-6" x-data="{
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
        submit(e) {
            if (!this.hasDrawn) {
                e.preventDefault();
                alert('Merci de signer dans le cadre ci-dessus avant de valider.');
                return;
            }
            this.$refs.signatureInput.value = this.$refs.canvas.toDataURL('image/png');
        },
    }"
        x-init="init()">
        <h3 class="text-base font-semibold text-fg mb-1">Votre signature</h3>
        <p class="text-sm text-muted mb-3">Dessinez votre signature dans le cadre ci-dessous à l'aide de la souris ou
            du doigt.</p>

        <canvas x-ref="canvas" class="w-full touch-none rounded-lg border border-line-soft bg-white"
            style="height: 180px;" x-on:pointerdown="start($event)" x-on:pointermove="draw($event)"
            x-on:pointerup="stop()" x-on:pointerleave="stop()"></canvas>

        <button type="button" x-on:click="clear()" class="mt-2 text-sm text-muted hover:text-fg">
            {{ __('Effacer') }}
        </button>

        <form method="POST" action="{{ route('portal.document-requests.sign', $generatedDocument) }}"
            x-on:submit="submit($event)" class="mt-6 border-t border-line pt-6">
            @csrf
            <input type="hidden" name="signature_data" x-ref="signatureInput">

            <label class="flex items-start gap-2">
                <input type="checkbox" name="consent" value="1" required
                    class="mt-0.5 rounded border-line text-brand shadow-sm focus:ring-brand">
                <span class="text-sm text-muted">
                    Je certifie avoir lu et compris le contenu de ce document, et j'accepte de le signer
                    électroniquement.
                </span>
            </label>
            <x-input-error :messages="$errors->get('consent')" class="mt-2" />
            <x-input-error :messages="$errors->get('signature_data')" class="mt-2" />

            <div class="mt-4 flex justify-end">
                <x-primary-button type="submit">{{ __('Signer et valider') }}</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
