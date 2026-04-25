# Dark Mode

A lightweight Alpine.js-based dark mode toggle with localStorage persistence and system preference fallback.

## Setup

Two pieces, both required. The JS store handles runtime toggling; the Blade partial prevents a flash-of-wrong-theme (FOWT) on cold loads.

### 1. Import the JS store

In your theme's `resources/src/js/app.js`:

```js
import '@tipowerup/ti-theme-toolkit/js/dark-mode';

// Rest of your theme JS
```

The module registers an Alpine store on the `alpine:init` event. No additional configuration needed.

### 2. Include the anti-FOWT partial in `<head>`

In your theme's `resources/views/includes/head.blade.php` (or equivalent), as early as possible — **before** any stylesheet import:

```blade
@include('tipowerup.theme-toolkit::_partials.dark-mode-head')
```

The partial emits a tiny synchronous inline `<script data-navigate-track>` that reads `localStorage.darkMode` and applies the `.dark` class to `<html>` before the first paint. Without this, there's a brief flash between HTML parse and Alpine's async init where the page renders in the wrong theme. `data-navigate-track` keeps the script alive across Livewire `wire:navigate` transitions so the flash doesn't recur.

The partial uses the **same** localStorage key and system-preference rules as the JS store, so the two stay in sync.

## Alpine Store API

Access the dark mode store via `Alpine.store('darkMode')`:

### store.value : boolean

The current dark mode state (reactive).

```js
Alpine.store('darkMode').value  // true (dark) or false (light)
```

Use in your HTML to bind UI state:

```html
<button x-show="!Alpine.store('darkMode').value">
    Switch to Dark Mode
</button>
```

### store.toggle() : void

Flip the current mode and persist the choice to localStorage.

```js
Alpine.store('darkMode').toggle();
```

### store.set(isDark) : void

Explicitly set dark mode on or off.

```js
Alpine.store('darkMode').set(true);   // Enable dark mode
Alpine.store('darkMode').set(false);  // Enable light mode
```

### store.init() : void

Called automatically by Alpine when the store is registered. Applies the initial class based on localStorage or system preference.

## HTML Pattern

### Toggle Button

```html
<button
    x-on:click="$store.darkMode.toggle()"
    :aria-pressed="$store.darkMode.value"
    class="p-2 rounded hover:bg-surface"
>
    <i :class="$store.darkMode.value ? 'fa fa-sun' : 'fa fa-moon'"></i>
</button>
```

### Show/Hide Based on Mode

```html
<div x-show="!$store.darkMode.value" class="light-only">
    Light mode content
</div>

<div x-show="$store.darkMode.value" class="dark-only">
    Dark mode content
</div>
```

### Conditionally Apply Classes

```html
<div
    :class="{
        'bg-white text-black': !$store.darkMode.value,
        'bg-gray-900 text-white': $store.darkMode.value,
    }"
>
    Content
</div>
```

Or use Tailwind's `dark:` prefix — the toolkit applies the `dark` class to `<html>` automatically.

## Storage

The store uses `localStorage.darkMode` with two possible values:

| Value | Meaning |
|-------|---------|
| `'dark'` | User explicitly chose dark mode |
| `'light'` | User explicitly chose light mode |
| (absent) | No explicit choice; follow system preference |

When the localStorage key is absent, the system `prefers-color-scheme: dark` media query is used.

## CSS Class Strategy

The store applies the `dark` class to `<html>` when dark mode is active:

```html
<!-- Light mode -->
<html>

<!-- Dark mode -->
<html class="dark">
```

Tailwind's `dark:` prefix utilities work automatically:

```html
<div class="bg-white dark:bg-slate-900 text-black dark:text-white">
    Switches background and text color in dark mode
</div>
```

Custom CSS using `.dark`:

```css
@layer utilities {
    .my-card {
        @apply bg-white text-black;
    }

    .dark .my-card {
        @apply bg-gray-800 text-white;
    }
}
```

Or use CSS custom properties (the toolkit provides `--color-*` tokens that switch in dark mode):

```css
body {
    background: rgb(var(--color-body));
    color: rgb(var(--color-text));
}
```

## Events

The store emits a custom `darkmode:changed` event on `window` when the mode changes. Non-Alpine code can listen:

```js
window.addEventListener('darkmode:changed', (e) => {
    console.log('Dark mode:', e.detail.value);
    // Perform custom actions
});
```

## System Preference Changes

The store watches the system `prefers-color-scheme` media query and automatically follows changes **only if the user has not made an explicit choice** (localStorage key is absent).

Example:
- User has not selected a mode → system dark mode enabled → store switches to dark.
- User explicitly selected dark mode → system switches to light → store stays dark (user's choice takes precedence).

To reset to system preference, clear localStorage:

```js
localStorage.removeItem('darkMode');
location.reload();
```

## wire:navigate Compatibility

After Livewire's `wire:navigate` swaps the DOM, the store re-applies the persisted preference to ensure the `dark` class survives the morph. This is handled automatically on the `livewire:navigated` event.

## Customizing Colors

Dark mode colors are defined in `tokens.css`:

```css
:root {
    --color-text: 33 37 41;      /* Light mode */
}

.dark {
    --color-text: 248 249 250;   /* Dark mode */
}
```

To customize, override these in your theme's `app.css`:

```css
@import '@tipowerup/ti-theme-toolkit/css/tokens.css';

.dark {
    --color-text: 220 220 220;   /* Your custom dark text */
}
```

## Example: Full Toggle Component

```html
<div class="flex items-center gap-2">
    <button
        x-on:click="$store.darkMode.toggle()"
        :aria-pressed="$store.darkMode.value"
        :aria-label="$store.darkMode.value ? 'Switch to light mode' : 'Switch to dark mode'"
        class="p-2 rounded-lg transition-colors
                bg-slate-100 text-slate-900
                dark:bg-slate-800 dark:text-slate-100
                hover:bg-slate-200 dark:hover:bg-slate-700
                focus:outline-none focus:ring-2 focus:ring-primary"
    >
        <i x-show="!$store.darkMode.value" class="fa fa-moon w-5 h-5"></i>
        <i x-show="$store.darkMode.value" class="fa fa-sun w-5 h-5"></i>
    </button>

    <span class="text-sm text-slate-600 dark:text-slate-400">
        <span x-show="!$store.darkMode.value">Light</span>
        <span x-show="$store.darkMode.value">Dark</span>
    </span>
</div>
```

## Debugging

Check the current state in the browser console:

```js
Alpine.store('darkMode').value          // Current state
localStorage.getItem('darkMode')        // Persisted choice
document.documentElement.classList      // Check for 'dark' class
```

Listen to storage changes (e.g., multiple tabs):

```js
window.addEventListener('storage', (e) => {
    if (e.key === 'darkMode') {
        location.reload();  // Sync across tabs
    }
});
```

## Related

- [Color system](color-system.md)
- [Tailwind preset](tailwind-preset.md)
- [Getting started](getting-started.md)
