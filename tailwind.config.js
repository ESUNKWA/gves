import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Backed by a CSS variable (set per-request from CompanySetting::primary_color)
                // so every client can have their own brand accent without a rebuild.
                brand: {
                    DEFAULT: 'rgb(var(--color-primary) / <alpha-value>)',
                },
                // Warm, self-switching design tokens (see resources/css/app.css) — each
                // resolves to a different value under prefers-color-scheme: dark, so a
                // single `bg-surface` class works in both themes without a `dark:` pair.
                ink: {
                    DEFAULT: '#15191a',
                    2: '#1d2224',
                },
                paper: 'rgb(var(--paper) / <alpha-value>)',
                surface: {
                    DEFAULT: 'rgb(var(--surface) / <alpha-value>)',
                    2: 'rgb(var(--surface-2) / <alpha-value>)',
                },
                line: {
                    DEFAULT: 'rgb(var(--line) / <alpha-value>)',
                    soft: 'rgb(var(--line-soft) / <alpha-value>)',
                },
                fg: 'rgb(var(--fg) / <alpha-value>)',
                muted: 'rgb(var(--muted) / <alpha-value>)',
                faint: 'rgb(var(--faint) / <alpha-value>)',
                success: {
                    DEFAULT: 'rgb(var(--success) / <alpha-value>)',
                    soft: 'rgb(var(--success-soft) / <alpha-value>)',
                },
                warning: {
                    DEFAULT: 'rgb(var(--warning) / <alpha-value>)',
                    soft: 'rgb(var(--warning-soft) / <alpha-value>)',
                },
                danger: {
                    DEFAULT: 'rgb(var(--danger) / <alpha-value>)',
                    soft: 'rgb(var(--danger-soft) / <alpha-value>)',
                },
            },
            boxShadow: {
                card: '0 1px 2px rgb(29 27 23 / 0.04), 0 6px 20px -8px rgb(29 27 23 / 0.10)',
                pop: '0 4px 10px rgb(29 27 23 / 0.06), 0 16px 40px -12px rgb(29 27 23 / 0.18)',
            },
        },
    },

    plugins: [forms],
};
