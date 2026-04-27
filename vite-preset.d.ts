import type { PluginOption, UserConfig } from 'vite';

export interface ToolkitPresetOptions {
    /** TastyIgniter theme code; sets `base` to `/vendor/<themeCode>/`. */
    themeCode: string;
    /**
     * Rollup input. Defaults to the standard theme layout
     * (`{ app: 'resources/src/js/app.{ts,js}', styles: 'resources/src/css/app.css' }`).
     * Auto-detects `.ts` over `.js` when both exist.
     */
    input?: Record<string, string> | string[];
    /** Extra Vite plugins merged after the toolkit's `@tailwindcss/vite` plugin. */
    plugins?: PluginOption[];
}

/**
 * Build a Vite config object suitable for a TiPowerUp theme package.
 *
 *   import { defineConfig } from 'vite';
 *   import { toolkitPreset } from '@tipowerup/ti-theme-toolkit/vite-preset';
 *
 *   export default defineConfig(toolkitPreset({ themeCode: 'my-theme' }));
 */
export function toolkitPreset(options: ToolkitPresetOptions): UserConfig;
