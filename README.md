# TiPowerUp Theme Toolkit

A shared PHP + frontend infrastructure package for TastyIgniter themes. Ship a custom theme in under 50 lines of PHP by extending `AbstractThemeServiceProvider`, inheriting color palettes, form widgets, Livewire features, dark mode, and a curated field schema.

## Requirements

- PHP 8.2+
- TastyIgniter 4.0+
- Livewire 3.0+
- Node 18+ (for frontend assets)
- Tailwind CSS 3.0+
- Vite 5.0+
- Alpine.js 3.0+

## Installation

### Composer

```bash
composer require tipowerup/ti-theme-toolkit
```

### npm

```bash
npm install --save-dev @tipowerup/ti-theme-toolkit
```

For local development (monorepo setup), use the `file:` protocol:

```bash
npm install --save-dev file:../ti-theme-toolkit
```

## Quickstart

A minimal theme using the toolkit:

### 1. Extend AbstractThemeServiceProvider

```php
<?php
namespace MyVendor\MyTheme;

use TiPowerUp\ThemeToolkit\AbstractThemeServiceProvider;

class ServiceProvider extends AbstractThemeServiceProvider
{
    protected function themeCode(): string { return 'my-theme'; }
    protected function phpNamespace(): string { return 'MyVendor\\MyTheme'; }
    protected function viewsPath(): string { return __DIR__.'/../resources/views'; }
    protected function langPath(): string { return __DIR__.'/../resources/lang'; }
    protected function livewirePath(): string { return __DIR__.'/Livewire'; }
    protected function bladeComponentsPath(): string { return __DIR__.'/View/Components'; }
}
```

### 2. Compose fields.php

```php
<?php
use TiPowerUp\ThemeToolkit\Fields\BaseSchema;

return [
    'form' => BaseSchema::merge(
        ['tabs' => BaseSchema::tabs()],
        ['tabs' => [
            'colors' => [
                'fields' => [
                    'color[primary]' => ['default' => '#0f172a'],
                ],
            ],
        ]],
    )['tabs'],
];
```

### 3. Configure Tailwind

```js
import toolkit from '@tipowerup/ti-theme-toolkit/tailwind-preset';

export default {
  presets: [toolkit],
  content: ['./resources/**/*.blade.php', './resources/src/**/*.{js,ts}'],
  theme: { extend: {} },
};
```

### 4. Configure Vite

```js
import { defineConfig } from 'vite';
import { toolkitPreset } from '@tipowerup/ti-theme-toolkit/vite-preset';

export default defineConfig({
  ...toolkitPreset({
    input: ['resources/src/css/app.css', 'resources/src/js/app.js'],
  }),
});
```

### 5. Entry CSS & JS

**resources/src/css/app.css:**
```css
@import '@tipowerup/ti-theme-toolkit/css/tokens.css';
@tailwind base;
@tailwind components;
@tailwind utilities;
/* Theme-specific styles */
```

**resources/src/js/app.js:**
```js
import '@tipowerup/ti-theme-toolkit/js/dark-mode';
// Theme-specific JS
```

### 6. Build & Publish

```bash
npm install && npm run build
php artisan igniter:theme-vendor-publish --force
```

## What You Get

- **AbstractThemeServiceProvider** — Base provider that wires views, translations, Livewire, Blade components, routing, and theme data. [Learn more →](docs/service-provider.md)
- **BaseSchema** — 7-tab field structure (general, banners, colors, dark mode, social, advanced, GDPR) + merge helper. [Learn more →](docs/fields-schema.md)
- **ColorHelper** — Derives primary color palettes automatically. [Learn more →](docs/color-system.md)
- **ThemePayloadResolver** — Resolves theme data and builds brand-style CSS for server-side rendering. [Learn more →](docs/color-system.md)
- **BannerManager** — Form widget for hero banner slides. [Learn more →](docs/form-widgets/banner-manager.md)
- **Dark Mode Store** — Alpine.js store for toggling dark mode with localStorage persistence. [Learn more →](docs/dark-mode.md)
- **Tailwind Preset** — Color tokens, darkMode class strategy, safelist. [Learn more →](docs/tailwind-preset.md)
- **Vite Preset** — Build config for asset pipeline. [Learn more →](docs/vite-preset.md)

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│ Theme (Child ServiceProvider + Views)                        │
├─────────────────────────────────────────────────────────────┤
│  AbstractThemeServiceProvider                               │
│  ├─ Registers form widgets (BannerManager)                 │
│  ├─ Loads views, translations, Livewire components         │
│  ├─ Shares theme payload via View composer                 │
│  └─ Defines routes                                         │
├─────────────────────────────────────────────────────────────┤
│  ThemePayloadResolver                                       │
│  ├─ Reads theme data from DB                               │
│  ├─ ColorHelper::derivePrimaryPalette()                    │
│  └─ buildBrandStyle() → <html style="--color-*: ...">     │
├─────────────────────────────────────────────────────────────┤
│ tokens.css (CSS custom property defaults)                   │
│ Tailwind preset (theme.colors.primary = rgb(var(...)))     │
│ dark-mode.js (Alpine store, class toggle)                  │
├─────────────────────────────────────────────────────────────┤
│ Blade Templates (use Tailwind utilities + CSS vars)        │
└─────────────────────────────────────────────────────────────┘
```

**Data flow:**
1. Admin saves theme colors via the theme customizer.
2. `ThemePayloadResolver::resolve()` reads from `themes.data` column.
3. `ColorHelper::derivePrimaryPalette()` derives shades (light, dark, etc.).
4. `buildBrandStyle()` outputs CSS variables as an inline `<html style>` attribute.
5. Blade views render utilities like `bg-primary`, `text-primary-400`.
6. Tailwind maps `primary` to `rgb(var(--color-primary) / <alpha>)` via the preset.
7. Dark mode JS toggles `html.dark` class; tokens.css `.dark { --color-*: ... }` applies dark variants.

## Versioning

This package follows [Semantic Versioning](https://semver.org/). Version `0.x` indicates early-stage development; the public API may change. Once the API stabilizes, `1.0.0` will be released.

During active development, pin an exact version in your theme's `composer.json`:

```json
{
  "require": {
    "tipowerup/ti-theme-toolkit": "0.1.0"
  }
}
```

Once stable, you may use `^0.x` or `^1.0`.

## License

MIT. See [LICENSE](LICENSE) for details.

---

**Next steps:** [Create a new theme](docs/getting-started.md) | [Migrate an existing theme](docs/migration-from-v0.md) | [Full documentation](docs/)
