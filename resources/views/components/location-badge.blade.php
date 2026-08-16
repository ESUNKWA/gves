@props(['ip' => null, 'latitude' => null, 'longitude' => null])

@if ($ip || $latitude)
    @php
        $tooltip = collect([
            $ip ? "IP : {$ip}" : null,
            $latitude && $longitude ? 'Position GPS disponible' : null,
        ])->filter()->implode(' · ');
    @endphp
    <a
        @if ($latitude && $longitude)
            href="https://www.google.com/maps?q={{ $latitude }},{{ $longitude }}"
            target="_blank"
            rel="noopener"
        @endif
        title="{{ $tooltip }}"
        {{ $attributes->merge(['class' => 'inline-flex align-middle text-muted hover:text-brand']) }}
    >
        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
        </svg>
    </a>
@endif
