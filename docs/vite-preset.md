# Vite Preset

A Vite configuration preset for building TastyIgniter theme assets with deterministic output paths, manifest versioning, and integration with the TastyIgniter asset pipeline.

## Installation

The preset is exported as a named export `toolkitPreset` from `@tipowerup/ti-theme-toolkit/vite-preset`.

### Usage

In your theme's `vite.config.js`:

```js
import { defineConfig } from 'vite';
import { toolkitPreset } from '@tipowerup/ti-theme-toolkit/vite-preset';

export default defineConfig({
    ...toolkitPreset({
        input: [
            'resources/src/css/app.css',
            'resources/src/js/app.js',
        ],
    }),
});
```

## Options

### input : string[]

Array of entry points relative to the package root.

```js
toolkitPreset({
    input: ['resources/src/css/app.css', 'resources/src/js/app.js'],
})
```

Required. Used to build the Rollup input configuration.

## What the Preset Provides

### Build Configuration

```js
{
    publicDir: false,               // TastyIgniter manages static assets
    build: {
        outDir: 'public',           // Output to public/
        emptyOutDir: false,         // Don't delete unrelated files
        manifest: true,             // Generate manifest.json for versioning
    },
    // ... Rollup options (see below) ...
}
```

### Rollup Output

**Entry files** are output to:
- `js/[name].js` — JavaScript entries
- `js/[name].js` — Chunk files

**CSS** is output to:
- `css/app.css` — All CSS (bundled into single file)

**Other assets** (fonts, images):
- `assets/[name][extname]` — Images, fonts, etc.

### Example Build Structure

Input:
```
resources/src/
├── css/
│   └── app.css
└── js/
    └── app.js
```

Output after `npm run build`:
```
public/
├── manifest.json                   # Asset manifest for cache-busting
├── css/
│   └── app.css                     # Compiled CSS
└── js/
    ├── app.js                      # Compiled JS
    ├── [chunk-name].js             # Auto-split chunks
    └── vendor-[hash].js            # Vendor code
```

### Manifest Example

```json
{
    "resources/src/css/app.css": {
        "file": "css/app.css",
        "src": "resources/src/css/app.css",
        "isEntry": true,
        "css": ["css/app.css"]
    },
    "resources/src/js/app.js": {
        "file": "js/app.js",
        "src": "resources/src/js/app.js",
        "isEntry": true
    }
}
```

## Customization

### Adding Extra Entry Points

```js
import { defineConfig } from 'vite';
import { toolkitPreset } from '@tipowerup/ti-theme-toolkit/vite-preset';

export default defineConfig({
    ...toolkitPreset({
        input: [
            'resources/src/css/app.css',
            'resources/src/js/app.js',
            'resources/src/js/admin.js',  // Extra entry
        ],
    }),
});
```

### Overriding Build Options

```js
export default defineConfig({
    ...toolkitPreset({ input: [...] }),

    // Override preset values
    build: {
        outDir: 'dist',                    // Custom output directory
        rollupOptions: {
            output: {
                entryFileNames: 'bundle/[name].js',  // Custom naming
            },
        },
    },
});
```

### Custom Asset Handling

To customize how assets are named/output:

```js
export default defineConfig({
    ...toolkitPreset({ input: [...] }),

    build: {
        rollupOptions: {
            output: {
                assetFileNames: (assetInfo) => {
                    const name = assetInfo.names?.[0] ?? assetInfo.name ?? '';

                    if (name.endsWith('.css')) {
                        return 'styles/[name].[hash][extname]';
                    }

                    if (/\.(gif|jpe?g|png|svg)$/.test(name)) {
                        return 'images/[name].[hash][extname]';
                    }

                    return 'assets/[name].[hash][extname]';
                },
            },
        },
    },
});
```

## Integration with TastyIgniter

The preset outputs to `public/` so files are available at `/vendor/[theme-code]/...` after running:

```bash
php artisan igniter:theme-vendor-publish --force
```

This copies compiled assets from `public/` to the main TastyIgniter's `public/vendor/[theme-code]/` directory.

### Asset Manifest Integration

If TastyIgniter's theme system reads the Vite manifest, you can reference assets by source path:

```blade
@vite('resources/src/css/app.css')  <!-- Automatically resolved to public/css/app.css -->
```

The manifest ensures cache-busting on rebuild.

## Development Workflow

### Watch Mode

```bash
npm run watch  # or: vite build --watch
```

Files in `public/` are rebuilt on every save. Refresh the browser to see changes.

### Production Build

```bash
npm run build
```

Minifies, chunks, and generates the manifest. Output is optimized for performance.

### Local Testing

1. Build: `npm run build`
2. Publish: `php artisan igniter:theme-vendor-publish --force`
3. Clear cache: `php artisan view:clear && php artisan config:clear`
4. Test in browser at the theme URL

## Troubleshooting

### "Module not found" errors during build

Ensure all import paths are relative:
```js
// ✓ Correct
import form from '@tipowerup/ti-theme-toolkit/tailwind-preset';
import '@tipowerup/ti-theme-toolkit/css/tokens.css';

// ✗ Wrong
import form from '@tipowerup/ti-theme-toolkit';
```

### CSS not minifying in production

The preset configures Vite to minify by default. If CSS appears unminified:
```bash
npm run build -- --mode production
```

### Asset paths wrong after publish

Ensure `igniter:theme-vendor-publish --force` is run from the TastyIgniter project root (not the theme directory).

### Public files missing after build

The preset sets `emptyOutDir: false` to avoid deleting unrelated files. If you need a clean build:

```bash
rm -rf public/
npm run build
```

## Related

- [Tailwind preset](tailwind-preset.md)
- [Getting started](getting-started.md)
- [Vite documentation](https://vitejs.dev/)
