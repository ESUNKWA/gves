@props(['label', 'active' => false])

<div x-data="{ open: @js($active) }">
    <button type="button" @click="open = !open"
        class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-[11px] font-semibold uppercase tracking-wider transition"
        :class="open ? 'text-slate-300' : 'text-slate-500 hover:text-slate-300'">
        <span>{{ $label }}</span>
        <svg class="h-3.5 w-3.5 shrink-0 transition-transform duration-150" :class="{ 'rotate-90': open }" fill="none"
            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
        </svg>
    </button>

    <div x-show="open" x-collapse.duration.150ms class="space-y-1 pt-1 pl-3">
        {{ $slot }}
    </div>
</div>
