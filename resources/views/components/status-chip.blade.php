@props(['tone' => 'neutral'])

@php
    $tones = [
        'success' => ['bg-success-soft text-success', 'bg-success'],
        'warning' => ['bg-warning-soft text-warning', 'bg-warning'],
        'danger' => ['bg-danger-soft text-danger', 'bg-danger'],
        'neutral' => ['bg-surface-2 text-muted', 'bg-faint'],
    ];
    [$badgeClasses, $dotClasses] = $tones[$tone] ?? $tones['neutral'];
@endphp

<span
    {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {$badgeClasses}"]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $dotClasses }}"></span>
    {{ $slot }}
</span>
