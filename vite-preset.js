/**
 * vite-preset.js — Vite configuration preset for TiPowerUp themes (Tailwind v4).
 *
 * Usage in your theme's vite.config.{js,ts}:
 *
 *   import { defineConfig } from 'vite';
 *   import { toolkitPreset } from '@tipowerup/ti-theme-toolkit/vite-preset';
 *
 *   export default defineConfig(toolkitPreset({ themeCode: 'my-theme' }));
 *
 * What this preset provides:
 * - `base: '/vendor/<themeCode>/'` (TastyIgniter asset path)
 * - `publicDir: false` (TastyIgniter handles static assets separately)
 * - `build.outDir: 'public'` with `emptyOutDir: false`
 * - `build.manifest: true` (for asset versioning / cache-busting)
 * - Deterministic output file names (`js/[name].js`, `css/app.css`,
 *   `assets/[name][extname]` for other assets)
 * - The official `@tailwindcss/vite` plugin, configured to process the theme's
 *   `app.css` directly (no PostCSS, no `tailwind.config.js`).
 *
 * `input` defaults to the standard theme layout
 * (`resources/src/js/app.{js,ts}` + `resources/src/css/app.css`); override
 * to change entry paths or add extra entries. Both `app.js` and `app.ts` are
 * accepted for the JS entry — the preset auto-detects which one exists.
 *
 * @module vite-preset
 */

import tailwindcss from '@tailwindcss/vite';
import { existsSync } from 'node:fs';
import { resolve } from 'node:path';

/**
 * Build a Vite config object suitable for a TiPowerUp theme package.
 *
 * @param {object} options
 * @param {string} options.themeCode  TastyIgniter theme code; sets `base` to
 *                                    `/vendor/<themeCode>/`.
 * @param {Record<string, string> | string[]} [options.input]  Rollup input.
 *        Defaults to the standard theme layout. Pick `app.ts` over `app.js`
 *        automatically when both are present (TS wins).
 * @param {import('vite').PluginOption[]} [options.plugins]  Extra Vite plugins
 *        merged after the toolkit's default `@tailwindcss/vite` plugin.
 * @returns {import('vite').UserConfig}
 */
export function toolkitPreset({ themeCode, input, plugins = [] } = {}) {
    if (!themeCode) {
        throw new Error('toolkitPreset: `themeCode` is required.');
    }

    // Default to the convention used by every TiPowerUp theme template.
    // Prefer `.ts` over `.js` if both exist so themes can opt into TypeScript
    // without changing config.
    if (!input) {
        const cwd = process.cwd();
        const tsEntry = resolve(cwd, 'resources/src/js/app.ts');
        const jsEntry = existsSync(tsEntry)
            ? 'resources/src/js/app.ts'
            : 'resources/src/js/app.js';

        input = {
            app: jsEntry,
            styles: 'resources/src/css/app.css',
        };
    }

    return {
        base: `/vendor/${themeCode}/`,
        publicDir: false,

        plugins: [tailwindcss(), ...plugins],

        build: {
            outDir: 'public',
            emptyOutDir: false,
            manifest: true,

            rollupOptions: {
                input,

                output: {
                    entryFileNames: 'js/[name].js',
                    chunkFileNames: 'js/[name].js',

                    /**
                     * Route CSS to `css/app.css` and all other assets to
                     * `assets/[name][extname]` (fonts, images, etc.).
                     *
                     * @param {import('rollup').PreRenderedAsset} assetInfo
                     * @returns {string}
                     */
                    assetFileNames: (assetInfo) => {
                        const name = assetInfo.names?.[0] ?? assetInfo.name ?? '';
                        if (name.endsWith('.css') || name === 'styles.css') {
                            return 'css/app.css';
                        }

                        return 'assets/[name][extname]';
                    },
                },
            },
        },
    };
}
