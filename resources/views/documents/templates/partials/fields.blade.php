@php
    $val = fn($field, $default = null) => $showErrors ? old($field, $default) : $default;
    $err = fn($field) => $showErrors ? $errors->get($field) : [];

    // Templates saved before the rich-text editor stored plain text with raw
    // newlines; the contenteditable div needs real <br> tags to display them
    // on separate lines, and (unlike the old plain textarea) its innerHTML is
    // rendered as trusted HTML, so the plain-text case must still be escaped.
    $rawContent = $val('content', $documentTemplate?->content) ?? '';
    $initialContent = str_contains($rawContent, '<') ? $rawContent : nl2br(e($rawContent));
@endphp

<div class="mt-6 grid grid-cols-1 gap-4" x-data="{
    fields: {{ Illuminate\Support\Js::from($val('fields', $documentTemplate?->fields ?? [])) }}.map((f) => ({
        ...f,
        // A locked field already has a stable, saved key (loaded from the
        // database) that must never change even if the label is edited
        // afterwards, or content already written against it would break.
        // A brand-new field isn't locked, so its key keeps tracking the
        // label live as it's typed.
        locked: !!f.key,
    })),
    addField() {
        this.fields.push({ key: '', label: '', type: 'text', required: false, locked: false });
    },
    removeField(index) {
        this.fields.splice(index, 1);
    },
    slug(label) {
        return (label || '')
            .toLowerCase()
            .normalize('NFD').replace(/[̀-ͯ]/g, '')
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '') || 'champ';
    },
    demandeTag(key) {
        // Built from single braces concatenated at runtime, never written as
        // an adjacent double-brace pair in the template source — otherwise
        // Blade's own compiler would try to parse that pair as an echo tag.
        const brace = '{';
        return brace + brace + 'demande.' + key + '}' + '}';
    },
    lastRange: null,
    saveSelection() {
        const selection = window.getSelection();
        if (selection.rangeCount > 0 && $refs.content.contains(selection.anchorNode)) {
            this.lastRange = selection.getRangeAt(0).cloneRange();
        }
    },
    restoreSelection() {
        $refs.content.focus();
        if (this.lastRange) {
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(this.lastRange);
        }
    },
    syncContent() {
        $refs.contentInput.value = $refs.content.innerHTML;
    },
    format(command) {
        // Toolbar buttons use mousedown.prevent so the selection is never
        // lost in the first place — restoring here would instead jump the
        // cursor back to a stale saved range from an earlier blur.
        $refs.content.focus();
        document.execCommand(command, false, null);
        this.syncContent();
    },
    insert(tag) {
        this.restoreSelection();
        document.execCommand('insertText', false, tag);
        this.syncContent();
    },
    steps: {{ Illuminate\Support\Js::from($val('approval_steps', $documentTemplate?->approval_steps ?? [])) }},
    stepLabels: {{ Illuminate\Support\Js::from($stepTypes) }},
    newStepType: '',
    stepLabel(type) {
        return this.stepLabels[type] || type;
    },
    addStep() {
        if (this.newStepType && !this.steps.includes(this.newStepType)) {
            this.steps.push(this.newStepType);
        }
        this.newStepType = '';
    },
    removeStep(index) {
        this.steps.splice(index, 1);
    },
    moveStep(index, offset) {
        const target = index + offset;
        if (target < 0 || target >= this.steps.length) return;
        [this.steps[index], this.steps[target]] = [this.steps[target], this.steps[index]];
    },
}">
    <div>
        <x-input-label for="name_{{ $id }}" value="Nom du gabarit" />
        <x-text-input name="name" id="name_{{ $id }}" value="{{ $val('name', $documentTemplate?->name) }}"
            class="mt-1" />
        <x-input-error :messages="$err('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="category_{{ $id }}" value="Catégorie" />
        <x-select name="category" id="category_{{ $id }}" class="mt-1">
            @foreach ($categories as $value => $label)
                <option value="{{ $value }}" @selected($val('category', $documentTemplate?->category) === $value)>
                    {{ $label }}</option>
            @endforeach
        </x-select>
        <x-input-error :messages="$err('category')" class="mt-2" />
    </div>

    <div>
        <x-input-label value="Champs à saisir par l'employé (optionnel)" />
        <p class="mt-1 text-xs text-muted">Par exemple, pour une demande d'avance sur salaire : « Montant » et «
            Motif ». L'employé les renseignera au moment de sa demande.</p>

        <div class="mt-2 space-y-2">
            <template x-for="(field, index) in fields" :key="index">
                <div class="flex flex-wrap items-center gap-2 rounded-lg border border-line-soft p-2">
                    <input type="hidden" :name="`fields[${index}][key]`" :value="field.key">
                    <input type="text" :name="`fields[${index}][label]`" x-model="field.label"
                        x-on:input="if (!field.locked) field.key = slug($event.target.value)"
                        placeholder="Nom du champ (ex : Montant)"
                        class="min-w-[10rem] flex-1 rounded-lg border-line bg-surface text-sm text-fg shadow-sm focus:border-brand focus:ring-brand">
                    <select :name="`fields[${index}][type]`" x-model="field.type"
                        class="rounded-lg border-line bg-surface text-sm text-fg shadow-sm focus:border-brand focus:ring-brand">
                        @foreach ($fieldTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <label class="flex shrink-0 items-center gap-1 text-xs text-muted">
                        <input type="hidden" :name="`fields[${index}][required]`" value="0">
                        <input type="checkbox" :name="`fields[${index}][required]`" value="1"
                            x-model="field.required" class="rounded border-line text-brand shadow-sm focus:ring-brand">
                        Obligatoire
                    </label>
                    <span class="shrink-0 font-mono text-xs text-faint"
                        x-text="field.key ? demandeTag(field.key) : ''"></span>
                    <button type="button" x-on:click="removeField(index)"
                        class="shrink-0 text-xs text-danger hover:underline">Retirer</button>
                </div>
            </template>
        </div>

        <button type="button" x-on:click="addField()" class="mt-2 text-sm text-brand hover:underline">+ Ajouter un
            champ</button>
        <x-input-error :messages="$err('fields')" class="mt-2" />
    </div>

    <div>
        <x-input-label value="Étapes de validation (optionnel)" />
        <p class="mt-1 text-xs text-muted">Si aucune étape n'est configurée, seul le demandeur signe le document
            (comportement actuel). Sinon, chaque étape devra valider dans l'ordre avant que le document soit
            finalisé.</p>

        <div class="mt-2 space-y-2">
            <template x-for="(type, index) in steps" :key="type">
                <div class="flex items-center gap-2 rounded-lg border border-line-soft p-2">
                    <span
                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-surface-2 text-xs font-medium text-muted"
                        x-text="index + 1"></span>
                    <input type="hidden" :name="`approval_steps[${index}]`" :value="type">
                    <span class="flex-1 text-sm text-fg" x-text="stepLabel(type)"></span>
                    <button type="button" x-on:click="moveStep(index, -1)" x-show="index > 0"
                        class="shrink-0 text-xs text-muted hover:text-fg">&uarr;</button>
                    <button type="button" x-on:click="moveStep(index, 1)" x-show="index < steps.length - 1"
                        class="shrink-0 text-xs text-muted hover:text-fg">&darr;</button>
                    <button type="button" x-on:click="removeStep(index)"
                        class="shrink-0 text-xs text-danger hover:underline">Retirer</button>
                </div>
            </template>
        </div>

        <div class="mt-2 flex flex-wrap items-center gap-2">
            <select x-model="newStepType"
                class="rounded-lg border-line bg-surface text-sm text-fg shadow-sm focus:border-brand focus:ring-brand">
                <option value="">Choisir une étape à ajouter…</option>
                <template x-for="[type, label] in Object.entries(stepLabels).filter(([t]) => !steps.includes(t))"
                    :key="type">
                    <option :value="type" x-text="label"></option>
                </template>
            </select>
            <button type="button" x-on:click="addStep()" x-bind:disabled="!newStepType"
                class="text-sm text-brand hover:underline disabled:opacity-50 disabled:no-underline">+ Ajouter</button>
        </div>
        <x-input-error :messages="$err('approval_steps')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="content_{{ $id }}" value="Contenu" />

        <div
            class="mt-1 flex flex-wrap items-center gap-1 rounded-t-lg border border-b-0 border-line bg-surface-2 px-2 py-1.5">
            <button type="button" title="Gras" x-on:mousedown.prevent="format('bold')"
                class="rounded px-2.5 py-1 text-sm font-bold text-fg hover:bg-surface">G</button>
            <button type="button" title="Italique" x-on:mousedown.prevent="format('italic')"
                class="rounded px-2.5 py-1 text-sm italic text-fg hover:bg-surface">I</button>
            <button type="button" title="Souligné" x-on:mousedown.prevent="format('underline')"
                class="rounded px-2.5 py-1 text-sm underline text-fg hover:bg-surface">S</button>
            <span class="mx-1 h-4 w-px bg-line"></span>
            <button type="button" title="Liste à puces" x-on:mousedown.prevent="format('insertUnorderedList')"
                class="rounded px-2.5 py-1 text-fg hover:bg-surface">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M4.5 6.75h.008v.008H4.5V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM4.5 12h.008v.008H4.5V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.008v.008H4.5v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
            </button>
        </div>

        <div id="content_{{ $id }}" x-ref="content" contenteditable="true"
            x-on:blur="saveSelection(); syncContent()" x-on:input="syncContent()"
            class="document-content block w-full resize-y overflow-y-auto rounded-b-lg border border-line bg-surface px-3 py-2 text-sm text-fg shadow-sm focus:border-brand focus:outline-none focus:ring-brand"
            style="height: 260px;">{!! $initialContent !!}</div>
        <input type="hidden" name="content" x-ref="contentInput" value="{{ $initialContent }}">
        <x-input-error :messages="$err('content')" class="mt-2" />

        <div class="mt-2 rounded-lg bg-surface-2 p-3">
            <p class="text-xs font-medium text-fg mb-2">Variables disponibles (cliquer pour insérer) :</p>
            <div class="space-y-2.5">
                @foreach ($variables as $group => $groupVariables)
                    <div>
                        <p class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-muted">
                            {{ $group }}</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($groupVariables as $tag => $label)
                                <button type="button" title="{{ $label }}"
                                    x-on:click="insert('{{ $tag }}')"
                                    class="rounded bg-surface px-1.5 py-0.5 text-xs font-mono text-brand border border-line-soft hover:bg-brand/10">{{ $tag }}</button>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div x-show="fields.filter((f) => f.key).length > 0" x-cloak>
                    <p class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-muted">Demande</p>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="field in fields.filter((f) => f.key)" :key="field.key">
                            <button type="button" x-on:click="insert(demandeTag(field.key))" :title="field.label"
                                x-text="demandeTag(field.key)"
                                class="rounded bg-surface px-1.5 py-0.5 text-xs font-mono text-brand border border-line-soft hover:bg-brand/10"></button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex items-center">
        <input type="hidden" name="is_active" value="0">
        <input name="is_active" id="is_active_{{ $id }}" type="checkbox" value="1"
            @checked($val('is_active', $documentTemplate?->is_active ?? true)) class="rounded border-line text-brand shadow-sm focus:ring-brand">
        <label for="is_active_{{ $id }}" class="ml-2 text-sm text-muted">Actif</label>
    </div>
</div>
