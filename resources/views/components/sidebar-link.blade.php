@props(['href', 'active' => false])

<a href="{{ $href }}"
    {{ $attributes->merge(['class' => 'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition ease-in-out duration-100 ' . ($active ? 'bg-white/[0.07] text-white [&_svg]:text-brand' : 'text-slate-400 hover:bg-white/[0.04] hover:text-white')]) }}>
    {{ $slot }}
</a>
