/**
 * Alpine.store-based dark-mode toggle. Side-effect import only — registers
 * an Alpine store and matching `darkMode()` x-data factory on `alpine:init`.
 *
 *   import '@tipowerup/ti-theme-toolkit/js/dark-mode';
 *
 * Reflects the current state to the `<html>` element's `dark` class and
 * persists it under `localStorage.darkMode`. Falls back to the OS-level
 * `prefers-color-scheme: dark` when no preference is stored.
 *
 * Emits `darkmode:changed` on `window` (`detail.value: boolean`) for
 * non-Alpine code to react.
 */
declare const _default: void;
export default _default;

/**
 * The Alpine store this module installs. Available at runtime as
 * `Alpine.store('darkMode')`.
 */
export interface DarkModeStore {
    /** Reactive — current dark-mode state. */
    value: boolean;
    /** Called automatically by Alpine on store registration. */
    init(): void;
    /** Flip the current mode and persist. */
    toggle(): void;
    /** Explicitly set the mode and persist. */
    set(isDark: boolean): void;
}

/**
 * The `x-data="darkMode()"` factory this module installs. Available at
 * runtime as part of Alpine's data registry.
 */
export interface DarkModeData {
    isDark: boolean;
    init(): void;
    /** Flip the current mode (delegates to the shared store). */
    toggleDarkMode(): void;
    /** Alias for `toggleDarkMode`. */
    toggle(): void;
}

declare global {
    interface WindowEventMap {
        'darkmode:changed': CustomEvent<{ value: boolean }>;
    }
}
