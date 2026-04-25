/**
 * dark-mode.js — Alpine.store-based dark mode toggle for TiPowerUp themes.
 *
 * Import this module in your theme's app.js (before Alpine initialises):
 *
 *   import '@tipowerup/ti-theme-toolkit/js/dark-mode';
 *
 * The store is registered on the `alpine:init` event so it is always available
 * before Alpine processes `x-data` directives. No further setup is needed.
 *
 * Alpine.store('darkMode') API:
 *   .value    {boolean}  — reactive, reflects current dark-mode state
 *   .toggle() {void}     — flip the current mode and persist
 *   .set(v)   {void}     — explicitly set to true (dark) or false (light)
 *   .init()   {void}     — called automatically by Alpine on store registration
 *
 * The store emits a custom `darkmode:changed` event on `window` whenever the
 * value changes, allowing non-Alpine code to react:
 *
 *   window.addEventListener('darkmode:changed', (e) => {
 *     console.log('dark:', e.detail.value);
 *   });
 *
 * Storage key: `localStorage.darkMode` — values: `'dark'` or `'light'`.
 * When absent, the system preference (`prefers-color-scheme: dark`) is used.
 *
 * @module dark-mode
 */

/**
 * Read the current dark-mode preference from localStorage, falling back to
 * the OS-level `prefers-color-scheme` media query.
 *
 * @returns {boolean} true if dark mode should be active
 */
function resolveInitialValue() {
    const saved = localStorage.getItem('darkMode');
    if (saved === 'dark') { return true; }
    if (saved === 'light') { return false; }

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

/**
 * Apply `dark` class to `<html>` and persist the preference.
 *
 * @param {boolean} isDark
 */
function applyDarkMode(isDark) {
    document.documentElement.classList.toggle('dark', isDark);
    localStorage.setItem('darkMode', isDark ? 'dark' : 'light');

    window.dispatchEvent(new CustomEvent('darkmode:changed', { detail: { value: isDark } }));
}

document.addEventListener('alpine:init', () => {
    // @ts-ignore — Alpine is loaded globally by Livewire
    Alpine.store('darkMode', {
        /** @type {boolean} */
        value: resolveInitialValue(),

        /**
         * Called automatically by Alpine when the store is registered.
         * Applies the initial class and watches for OS preference changes.
         */
        init() {
            applyDarkMode(this.value);

            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                // Only follow system changes when the user has not made an
                // explicit choice (i.e. localStorage key is absent).
                if (localStorage.getItem('darkMode') === null) {
                    this.set(e.matches);
                }
            });
        },

        /**
         * Toggle between dark and light mode.
         */
        toggle() {
            this.set(!this.value);
        },

        /**
         * Explicitly set dark mode on or off.
         *
         * @param {boolean} isDark
         */
        set(isDark) {
            this.value = isDark;
            applyDarkMode(isDark);
        },
    });
});

/**
 * Re-apply dark mode class after wire:navigate swaps the DOM. Livewire
 * replaces `<html>` attributes from the server response (which has no `dark`
 * class), so the Alpine store re-applies the persisted preference.
 */
document.addEventListener('livewire:navigated', () => {
    const isDark = resolveInitialValue();
    document.documentElement.classList.toggle('dark', isDark);
});
