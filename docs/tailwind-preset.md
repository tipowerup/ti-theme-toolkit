# Tailwind CSS Preset

A pre-configured Tailwind preset that provides color tokens, dark mode strategy, form styles, and a safelist of commonly used utilities.

## Installation

The preset is exported as a default export from `@tipowerup/ti-theme-toolkit/tailwind-preset`.

### Usage

In your theme's `tailwind.config.js`:

```js
import toolkit from '@tipowerup/ti-theme-toolkit/tailwind-preset';

export default {
    presets: [toolkit],
    content: ['./resources/**/*.blade.php', './resources/src/**/*.{js,ts}'],
    theme: {
        extend: {
            // Add theme-specific tokens here
        },
    },
};
```

## What the Preset Provides

### darkMode: 'class'

Dark mode is toggled via the `dark` class on `<html>`. The Alpine store `Alpine.store('darkMode')` handles the toggle automatically.

```html
<!-- Light mode -->
<html>

<!-- Dark mode -->
<html class="dark">
```

Tailwind utilities use the `dark:` prefix:

```html
<div class="bg-white dark:bg-slate-900">
    Light mode: white background
    Dark mode: slate-900 background
</div>
```

### Color Tokens

Extended theme colors that map to CSS custom properties:

```js
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
    secondary: { ... },
    success: '...',
    danger: '...',
    warning: '...',
    info: '...',
    text: { DEFAULT, muted },
    body: '...',
    surface: '...',
    border: '...',
}
```

These reference CSS custom properties defined in `tokens.css` and injected at runtime by the PHP color system. No rebuild needed when admin changes colors.

### Font Family

Default sans-serif is set to Inter (from Google Fonts):

```js
fontFamily: {
    sans: ['Inter', 'system-ui', 'sans-serif'],
}
```

### Safelist

Commonly used utilities are pre-listed so Tailwind doesn't purge them (they're often generated dynamically in Blade/PHP):

```js
safelist: [
    'bg-primary', 'text-primary', 'border-primary',
    'bg-primary/10', 'bg-primary/20',
    'text-primary-400', 'text-primary-dark', 'text-primary-light',
    'bg-secondary', 'bg-secondary-dark',
    'bg-success', 'text-success',
    'bg-danger', 'text-danger',
    'bg-warning', 'text-warning',
    'bg-info', 'text-info',
    'bg-body', 'bg-surface', 'text-text', 'text-text-muted', 'border-border',
    // Dark mode variants
    'dark:bg-body', 'dark:bg-surface', 'dark:text-text', 'dark:text-text-muted',
    'dark:border-border',
    // ... etc
]
```

### Forms Plugin

The `@tailwindcss/forms` plugin is included for consistent form element styling (inputs, buttons, selects, checkboxes, etc.).

## Extending the Preset

Override or extend the preset in your child theme's `tailwind.config.js`:

### Adding a Custom Color

```js
export default {
    presets: [toolkit],
    theme: {
        extend: {
            colors: {
                accent: 'rgb(var(--color-accent) / <alpha-value>)',
            },
        },
    },
};
```

Then define the CSS variable in your `app.css`:

```css
@import '@tipowerup/ti-theme-toolkit/css/tokens.css';

:root {
    --color-accent: 212 96 13;
}

.dark {
    --color-accent: 255 185 97;
}
```

Or let the PHP color system inject it (see [color system docs](color-system.md)).

### Overriding Fonts

```js
export default {
    presets: [toolkit],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Poppins', 'sans-serif'],
            },
        },
    },
};
```

### Customizing Safelist

If you use additional dynamic color utilities, add them to the safelist:

```js
export default {
    presets: [toolkit],
    safelist: [
        'bg-custom-color',
        'text-custom-color',
        'dark:bg-custom-color',
    ],
};
```

Or use regex patterns:

```js
safelist: [
    { pattern: /^(bg|text|border)-(primary|secondary|custom)/ },
]
```

### Merging Multiple Presets

You can use multiple presets together:

```js
import toolkit from '@tipowerup/ti-theme-toolkit/tailwind-preset';
import forms from '@tailwindcss/forms';  // If you want to use it separately

export default {
    presets: [toolkit],
    // toolkit already includes @tailwindcss/forms, no need to add it again
};
```

## CSS Variable Contract

The preset expects these CSS variables to be defined (fallbacks in `tokens.css`):

### Primary Palette
- `--color-primary`
- `--color-primary-50`, `--color-primary-100`, `--color-primary-400`
- `--color-primary-light`, `--color-primary-dark`, `--color-primary-900`

### Secondary & Semantic
- `--color-secondary` (and variants)
- `--color-success`, `--color-danger`, `--color-warning`, `--color-info`

### Neutral Tokens
- `--color-text`, `--color-text-muted`
- `--color-body`, `--color-surface`, `--color-border`

All values are "r g b" space-separated triplets (e.g., `249 115 22`) so they work with `rgb(var(...) / alpha)` syntax.

## Verification

After building, check that color utilities work:

```bash
npm run build
grep -c "bg-primary" public/css/app.css  # Should match (utility generated)
```

In your Blade template:

```blade
<div class="bg-primary text-white dark:bg-primary-900">
    Uses admin-selected primary color
</div>
```

## Related

- [Color system](color-system.md)
- [Dark mode](dark-mode.md)
- [Vite preset](vite-preset.md)
- [Tailwind CSS docs](https://tailwindcss.com/)
