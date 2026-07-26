@props(['label', 'value'])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-line-soft bg-surface shadow-card p-5']) }}>
    <div class="flex items-center gap-4">
        @isset($icon)
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand/10 text-brand">
                {{ $icon }}
            </span>
        @endisset
        <div class="min-w-0">
            <p class="text-sm text-muted">{{ $label }}</p>
            <p class="mt-0.5 text-2xl font-semibold text-fg">{{ $value }}</p>
        </div>
    </div>
</div>
