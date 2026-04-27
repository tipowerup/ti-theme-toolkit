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
 *       input: {
 *         app: 'resources/src/js/app.js',
 *         styles: 'resources/src/css/app.css',
 *       },
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
 * `input` is forwarded to Rollup unchanged. Pass an object whose keys are
 * the chunk names you want (`{ app: '...js', styles: '...css' }`) so JS
 * entries get stable `[name]` values in `entryFileNames`. Arrays also work
 * but Rollup auto-derives names from basenames, which produces collisions
 * (and `app2.js`-style suffixes) when multiple entries share a basename.
 *
 * @param {object} options
 * @param {Record<string, string> | string[]} options.input  Rollup input
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
