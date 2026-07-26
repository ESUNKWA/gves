@props(['disabled' => false])

<input @disabled($disabled)
    {{ $attributes->merge(['class' => 'block w-full rounded-lg border-line bg-surface text-sm text-fg shadow-sm placeholder:text-faint focus:border-brand focus:ring-brand disabled:opacity-50 dark:placeholder:text-muted dark:focus:border-brand dark:focus:ring-brand']) }}>
