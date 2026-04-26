/**
 * vite-preset.js — Vite configuration preset for TiPowerUp themes.
 *
 * Usage in your theme's vite.config.js:
 *
 *   import { defineConfig } from 'vite';
 *   import { toolkitPreset } from '@tipowerup/ti-theme-toolkit/vite-preset';
 *
 *   export default defineConfig({
 *     ...toolkitPreset({
 *       input: ['resources/src/css/app.css', 'resources/src/js/app.js'],
 *     }),
 *   });
 *
 * The preset configures:
 * - `publicDir: false` (TastyIgniter handles static assets separately)
 * - `build.outDir: 'public'` with `emptyOutDir: false`
 * - `build.manifest: true` (for asset versioning / cache-busting)
 * - Deterministic output file names (`js/[name].js`, `css/app.css`,
 *   `assets/[name][extname]` for other assets)
 *
 * @module vite-preset
 */

/**
 * Build a Vite config object suitable for a TiPowerUp theme package.
 *
 * @param {object}   options
 * @param {string[]} options.input   Entry point paths relative to the package
 *                                   root, e.g.
 *                                   `['resources/src/css/app.css', 'resources/src/js/app.js']`
 * @returns {import('vite').UserConfig}
 */
export function toolkitPreset({ input }) {
    return {
        publicDir: false,

        build: {
            outDir: 'public',
            emptyOutDir: false,
            manifest: true,

            rollupOptions: {
                /**
                 * Pass inputs as an array so Rollup derives entry names from
                 * each file's basename. Using an object keyed by basename
                 * silently drops collisions when two entries share a name
                 * (e.g. `app.css` + `app.js`). With the array form, Rollup
                 * handles JS entries via `entryFileNames` and routes the CSS
                 * to `assetFileNames`, so both emit correctly.
                 */
                input: input,

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
