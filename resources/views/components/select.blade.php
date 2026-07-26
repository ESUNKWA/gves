@props(['disabled' => false])

<select @disabled($disabled)
    {{ $attributes->merge(['class' => 'block w-full rounded-lg border-line bg-surface text-sm text-fg shadow-sm focus:border-brand focus:ring-brand disabled:opacity-50 dark:focus:border-brand dark:focus:ring-brand']) }}>
    {{ $slot }}
</select>
