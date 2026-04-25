# Color System and Palette Derivation

The toolkit's color system propagates admin-selected brand colors through PHP, CSS, and Tailwind utilities automatically. No rebuild needed when admin colors change.

## How It Works

```
Admin Theme Customizer
    ↓ (color[primary] = #f97316)
    ↓
Database (themes.data)
    ↓
ThemePayloadResolver::resolve()
    ├─ Reads themes.data column
    ├─ ColorHelper::derivePrimaryPalette()
    │  └─ Generates light/dark shades
    └─ buildBrandStyle()
       └─ Outputs CSS var string
           ↓
       <html style="--color-primary:249 115 22; --color-primary-50:255 247 237; ...">
           ↓
       tokens.css (:root defines fallbacks)
           ↓
       Tailwind preset (colors.primary = rgb(var(--color-primary) / <alpha>))
           ↓
       Blade utilities (bg-primary, text-primary-400, etc.)
```

## ColorHelper

Static utility class for color math.

### ColorHelper::hexToRgb(string $hex) : string

Converts hex color to "r g b" space-separated string (for `rgb(var(...) / <alpha>)` syntax).

```php
use TiPowerUp\ThemeToolkit\Support\ColorHelper;

ColorHelper::hexToRgb('#f97316')  // '249 115 22'
ColorHelper::hexToRgb('#fff')     // '255 255 255'
```

### ColorHelper::tint(string $hex, float $amount) : string

Mix a color toward white. `$amount` 0..1 (higher = closer to white).

```php
ColorHelper::tint('#f97316', 0.25)  // Mix 25% toward white
```

### ColorHelper::shade(string $hex, float $amount) : string

Mix a color toward black. `$amount` 0..1 (higher = closer to black).

```php
ColorHelper::shade('#f97316', 0.15)  // Mix 15% toward black
```

### ColorHelper::derivePrimaryPalette(string $hex) : array

Apply `PRIMARY_SHADE_MAP` ratios to generate a full palette from a single hex color.

```php
$palette = ColorHelper::derivePrimaryPalette('#f97316');

// Returns:
// [
//     '--color-primary' => '249 115 22',
//     '--color-primary-50' => '255 247 237',
//     '--color-primary-100' => '255 237 213',
//     '--color-primary-400' => '251 146 60',
//     '--color-primary-light' => '253 186 116',
//     '--color-primary-dark' => '234 88 12',
//     '--color-primary-900' => '124 45 18',
// ]
```

### ColorHelper::PRIMARY_SHADE_MAP

The ratios applied to the primary color to derive the palette:

```php
public const PRIMARY_SHADE_MAP = [
    '--color-primary-50' => -0.95,      // 95% tint toward white
    '--color-primary-100' => -0.88,     // 88% tint
    '--color-primary-400' => -0.20,     // 20% tint
    '--color-primary-900' => 0.70,      // 70% shade toward black
    '--color-primary-light' => -0.25,   // 25% tint (hover variant)
    '--color-primary-dark' => 0.15,     // 15% shade (active variant)
];
```

Negative values = tint (toward white); positive values = shade (toward black).

## ThemePayloadResolver

Instance singleton that resolves theme data per request and builds the brand-style CSS string. Registered as a singleton by `ToolkitServiceProvider` (the package's own provider — not by `AbstractThemeServiceProvider`), so it's available in every request whether or not the child theme overrides `register()`.

### resolve() : array

Returns the resolved theme payload for the currently active theme, cached per request.

```php
use TiPowerUp\ThemeToolkit\Support\ThemePayloadResolver;

$resolver = app(ThemePayloadResolver::class);
$payload = $resolver->resolve();

// Returns:
// [
//     'themeData' => [...],           // Raw theme form data
//     'themeBrandStyle' => '...',     // CSS inline style string for <html>
//     'primary' => '#f97316',         // Primary hex value (for Livewire progress bar)
// ]
```

### buildBrandStyle(array $themeData) : string

Build the CSS variable style string from theme data. Called automatically by `resolve()`.

Returns a string suitable for the `<html style="">` attribute:

```php
buildBrandStyle(['color' => ['primary' => '#f97316', 'secondary' => '#6b7280']])

// Returns: '--color-primary:249 115 22;--color-primary-50:255 247 237;...'
```

### flush() : void

Clear the internal request cache. Useful in tests.

```php
$resolver->flush();
```

### Security: color value validation

Before emitting any `--color-*: r g b;` declaration, `buildBrandStyle()` verifies each value matches `^\d{1,3} \d{1,3} \d{1,3}$`. Malformed values are silently skipped. Combined with `ColorHelper::parseHex()`'s regex-guarded input (which returns `[0,0,0]` on invalid hex), this closes the HTML-attribute-injection surface that would otherwise exist in `<html style="{!! $themeBrandStyle !!}">`. Any new caller of `buildBrandStyle()` inherits the guard automatically.

## CSS Custom Properties

### Primary Palette

Generated automatically from `color[primary]`:

| Property | Meaning |
|----------|---------|
| `--color-primary` | Base color |
| `--color-primary-50` | Very light tint |
| `--color-primary-100` | Light tint |
| `--color-primary-400` | Medium tint |
| `--color-primary-light` | Hover tint (25%) |
| `--color-primary-dark` | Active shade (15%) |
| `--color-primary-900` | Very dark shade |

### Semantic Colors

From `color[secondary]`, `color[success]`, etc.:

| Property | Source |
|----------|--------|
| `--color-secondary` | `color[secondary]` |
| `--color-secondary-light` | (optional) `color[secondary_light]` |
| `--color-secondary-dark` | (optional) `color[secondary_dark]` |
| `--color-success` | `color[success]` |
| `--color-danger` | `color[danger]` |
| `--color-warning` | `color[warning]` |
| `--color-info` | `color[info]` |

### Neutral Tokens

Not derived; set independently:

| Property | Purpose |
|----------|---------|
| `--color-text` | Main text color |
| `--color-text-muted` | Secondary/muted text |
| `--color-body` | Page background |
| `--color-surface` | Card/panel background |
| `--color-border` | Border/divider color |

These switch values in dark mode via the `.dark` selector in `tokens.css`.

## Tailwind Integration

The `tailwind-preset.js` maps theme colors to CSS variables:

```js
colors: {
    primary: {
        DEFAULT: 'rgb(var(--color-primary) / <alpha-value>)',
        50: 'rgb(var(--color-primary-50) / <alpha-value>)',
        100: 'rgb(var(--color-primary-100) / <alpha-value>)',
        400: 'rgb(var(--color-primary-400) / <alpha-value>)',
        light: 'rgb(var(--color-primary-light) / <alpha-value>)',
        dark: 'rgb(var(--color-primary-dark) / <alpha-value>)',
        900: 'rgb(var(--color-primary-900) / <alpha-value>)',
    },
    // ... secondary, semantic, neutral ...
}
```

This allows utilities like `bg-primary`, `text-primary-400`, `text-primary-dark` to read values from the inline `<html style="">` attribute dynamically.

### No Rebuild Needed

When admin changes `color[primary]`:
1. Page reloads (or wire:navigate).
2. Server renders `<html style="--color-primary:...">`.
3. Tailwind utilities immediately read the new CSS var value.
4. No Tailwind rebuild needed because utilities reference vars, not hardcoded hex values.

## Adding Custom Color Tokens

To add a custom color that isn't in `BaseSchema`:

### Option 1: Theme-Specific Override

In your theme's `resources/meta/fields.php`, add a new color field:

```php
return [
    'form' => BaseSchema::merge(
        ['tabs' => BaseSchema::tabs()],
        ['tabs' => [
            'colors' => [
                'fields' => [
                    'color[accent]' => [
                        'label' => 'Accent Color',
                        'type' => 'colorpicker',
                        'span' => 'left',
                        'rules' => 'nullable|string',
                    ],
                ],
            ],
        ]],
    )['tabs'],
];
```

Then update `tailwind.config.js` to include the new color:

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

The toolkit's `ThemePayloadResolver` does not automatically derive custom colors (only the whitelisted semantic colors). If you need a palette for your custom color, derive it in your ServiceProvider:

```php
use TiPowerUp\ThemeToolkit\Support\ColorHelper;

protected function boot(): void
{
    parent::boot();

    ViewFacade::composer('*', function (View $view): void {
        $resolver = app(ThemePayloadResolver::class);
        $payload = $resolver->resolve();
        $customColor = $payload['themeData']['color']['accent'] ?? null;

        if ($customColor) {
            $palette = ColorHelper::derivePrimaryPalette($customColor);
            // Inject into your own brand style or view data
        }
    });
}
```

### Option 2: Extend BaseSchema

To add a new semantic color to the toolkit itself (for all themes that extend it), edit `src/Fields/BaseSchema.php` and add to the `colors` tab. Then update the `tailwind-preset.js` and `tokens.css` accordingly.

## Dark Mode and Colors

Dark mode is handled separately by the `dark-mode.js` store and `tokens.css`. The neutral colors (`--color-text`, `--color-body`, etc.) switch values when `html.dark` is present:

```css
:root {
    --color-text: 33 37 41;      /* Light mode */
    --color-body: 255 255 255;
}

.dark {
    --color-text: 248 249 250;   /* Dark mode */
    --color-body: 33 37 41;
}
```

Primary and semantic colors are admin-selected and do NOT change in dark mode (they're designed to be visible on both light and dark backgrounds).

## Related

- [Tailwind preset reference](tailwind-preset.md)
- [BaseSchema and fields](fields-schema.md)
- [Dark mode store](dark-mode.md)
