@props(['title', 'description' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 mb-8 sm:flex-row sm:items-center sm:justify-between']) }}>
    <div class="min-w-0">
        <h1 class="text-2xl font-semibold tracking-tight text-fg">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 text-sm text-muted">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex shrink-0 items-center gap-3">
            {{ $actions }}
        </div>
    @endisset
</div>
