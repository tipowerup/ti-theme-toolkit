/**
 * tailwind-preset.js — Tailwind CSS preset for TiPowerUp themes.
 *
 * Usage in your theme's tailwind.config.js:
 *
 *   import toolkit from '@tipowerup/ti-theme-toolkit/tailwind-preset';
 *
 *   export default {
 *     presets: [toolkit],
 *     content: ['./resources/**\/*.blade.php', './resources/src/**\/*.{js,ts}'],
 *     theme: {
 *       extend: {
 *         // Add theme-specific token overrides here
 *       },
 *     },
 *   };
 *
 * What this preset provides:
 * - `darkMode: 'class'` strategy (toggled by Alpine.store('darkMode'))
 * - `theme.extend.colors` referencing CSS custom properties via
 *   `rgb(var(--color-*) / <alpha-value>)` so Tailwind utilities automatically
 *   pick up the admin-saved palette without a CSS rebuild.
 * - `safelist` for the most common palette utilities that Tailwind would
 *   otherwise purge (since they're often built dynamically in PHP/Blade).
 * - `@tailwindcss/forms` plugin for consistent form element styling.
 *
 * @type {import('tailwindcss').Config}
 */
export default {
    darkMode: 'class',

    theme: {
        extend: {
            colors: {
                primary: {
                    DEFAULT: 'rgb(var(--color-primary) / <alpha-value>)',
                    light: 'rgb(var(--color-primary-light) / <alpha-value>)',
                    dark: 'rgb(var(--color-primary-dark) / <alpha-value>)',
                    50: 'rgb(var(--color-primary-50) / <alpha-value>)',
                    100: 'rgb(var(--color-primary-100) / <alpha-value>)',
                    400: 'rgb(var(--color-primary-400) / <alpha-value>)',
                    500: 'rgb(var(--color-primary) / <alpha-value>)',
                    600: 'rgb(var(--color-primary) / <alpha-value>)',
                    700: 'rgb(var(--color-primary-dark) / <alpha-value>)',
                    900: 'rgb(var(--color-primary-900) / <alpha-value>)',
                },
                secondary: {
                    DEFAULT: 'rgb(var(--color-secondary) / <alpha-value>)',
                    light: 'rgb(var(--color-secondary-light) / <alpha-value>)',
                    dark: 'rgb(var(--color-secondary-dark) / <alpha-value>)',
                },
                success: 'rgb(var(--color-success) / <alpha-value>)',
                danger: 'rgb(var(--color-danger) / <alpha-value>)',
                warning: 'rgb(var(--color-warning) / <alpha-value>)',
                info: 'rgb(var(--color-info) / <alpha-value>)',
                text: {
                    DEFAULT: 'rgb(var(--color-text) / <alpha-value>)',
                    muted: 'rgb(var(--color-text-muted) / <alpha-value>)',
                },
                body: 'rgb(var(--color-body) / <alpha-value>)',
                surface: 'rgb(var(--color-surface) / <alpha-value>)',
                border: 'rgb(var(--color-border) / <alpha-value>)',
            },
            fontFamily: {
                sans: ['Inter', 'system-ui', 'sans-serif'],
            },
        },
    },

    /**
     * Safelist utilities that reference palette colors and are commonly
     * generated dynamically in Blade/PHP (e.g. status badges, dynamic
     * classes from PHP arrays), preventing Tailwind from purging them.
     */
    safelist: [
        // Primary
        'bg-primary', 'text-primary', 'border-primary', 'ring-primary',
        'bg-primary/10', 'bg-primary/20',
        'hover:bg-primary', 'hover:text-primary', 'focus:ring-primary',
        'text-primary-400', 'text-primary-dark', 'text-primary-light',
        'bg-primary-50', 'bg-primary-100',
        // Secondary
        'bg-secondary', 'text-secondary', 'border-secondary',
        'bg-secondary-dark', 'hover:bg-secondary-dark',
        // Semantic
        'bg-success', 'text-success', 'bg-success/10',
        'bg-danger', 'text-danger', 'bg-danger/10',
        'bg-warning', 'text-warning', 'bg-warning/10',
        'bg-info', 'text-info', 'bg-info/10',
        // Neutrals
        'bg-body', 'bg-surface', 'text-text', 'text-text-muted', 'border-border',
        // Dark mode variants for common utilities
        'dark:bg-body', 'dark:bg-surface', 'dark:text-text', 'dark:text-text-muted',
        'dark:border-border', 'dark:focus:ring-offset-body',
    ],

    plugins: [
        require('@tailwindcss/forms'),
    ],
};
