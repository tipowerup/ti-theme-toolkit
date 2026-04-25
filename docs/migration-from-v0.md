# Migrating an Existing Theme to the Toolkit

A step-by-step guide for adopting the toolkit in a theme that was built before the toolkit existed.

## Prerequisites

- Your theme is a standalone Composer package with its own ServiceProvider.
- It has a `resources/meta/fields.php` defining theme customizer fields.
- You've already identified shared patterns (color helpers, widgets, etc.) that would benefit from centralization.

## Step 1: Add the Toolkit Dependency

In your theme's `composer.json`:

```json
{
    "require": {
        "php": "^8.2",
        "tastyigniter/core": "^4.0",
        "tipowerup/ti-theme-toolkit": "^0.1"
    }
}
```

Then install:

```bash
composer update
composer dump-autoload
```

## Step 2: Refactor the ServiceProvider

### Before

Your current ServiceProvider might look like this:

```php
<?php

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'my-theme');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'my.theme');
        // ... form widgets, components, routes, view composers, etc.
    }
}
```

### After

Replace with `AbstractThemeServiceProvider`:

```php
<?php

declare(strict_types=1);

namespace MyVendor\MyTheme;

use TiPowerUp\ThemeToolkit\AbstractThemeServiceProvider;

class ServiceProvider extends AbstractThemeServiceProvider
{
    protected function themeCode(): string
    {
        return 'myvendor-mytheme';  // Your theme's code
    }

    protected function phpNamespace(): string
    {
        return 'MyVendor\\MyTheme';
    }

    protected function viewsPath(): string
    {
        return __DIR__.'/../resources/views';
    }

    protected function langPath(): string
    {
        return __DIR__.'/../resources/lang';
    }

    protected function livewirePath(): string
    {
        return __DIR__.'/Livewire';
    }

    protected function bladeComponentsPath(): string
    {
        return __DIR__.'/View/Components';
    }

    /**
     * Define any theme-specific routes here.
     */
    protected function routes(): array
    {
        return [];
    }
}
```

The abstract provider now handles:
- Loading views, translations, Livewire, Blade components
- Registering the BannerManager widget
- Creating a wildcard view composer to share `$theme`, `$page`, `$themeData`, `$themeBrandStyle`
- Page security checks
- Google Fonts configuration
- Route registration

**Removed from your ServiceProvider:**
- All `loadViewsFrom`, `loadTranslationsFrom` calls
- View composers for contact, GDPR, social, menu (now in abstract provider)
- Form widget registration code
- Livewire component scanner code
- Page security binding code

## Step 3: Delete Moved Classes

If your theme has any of these, delete them (the toolkit now provides them):

- `src/Support/ColorHelper.php` — Use `TiPowerUp\ThemeToolkit\Support\ColorHelper` instead
- `src/FormWidgets/BannerManager.php` — Toolkit provides it
- `src/Livewire/Features/SupportFlashMessages.php` — Toolkit provides it
- `src/Data/LocationData.php` — Toolkit provides it
- `src/Data/MenuItemData.php` — Toolkit provides it
- `src/Actions/ListMenuItems.php` — Toolkit provides it

Update any imports in remaining files:

```php
// Before
use MyVendor\MyTheme\Support\ColorHelper;

// After
use TiPowerUp\ThemeToolkit\Support\ColorHelper;
```

Also delete the BannerManager partials:
- `resources/views/_partials/formwidgets/bannermanager/bannermanager.blade.php`
- `resources/views/_partials/formwidgets/bannermanager/row.blade.php`

The toolkit provides these via its own view namespace (`tipowerup.theme-toolkit`).

## Step 4: Refactor fields.php

### Before

Your `resources/meta/fields.php` might have been a large flat array with all tabs and fields defined manually:

```php
<?php

return [
    'form' => [
        'tabs' => [
            'general' => [/* ... */],
            'colors' => [/* ... */],
            'banners' => [/* ... */],
            // ... etc
        ],
    ],
];
```

### After

Use `BaseSchema::merge()` to inherit the toolkit's 7-tab structure and override only what's unique to your theme:

```php
<?php

use TiPowerUp\ThemeToolkit\Fields\BaseSchema;

return [
    'form' => BaseSchema::merge(
        ['tabs' => BaseSchema::tabs()],
        ['tabs' => [
            // Override only your theme's brand defaults
            'colors' => [
                'fields' => [
                    'color[primary]' => [
                        'default' => '#your-brand-color',
                    ],
                ],
            ],
            // Add any theme-specific tabs if needed
            'my_custom_tab' => [
                'title' => 'My Custom Settings',
                'fields' => [
                    'my_field' => [/* ... */],
                ],
            ],
        ]],
    )['tabs'],
];
```

This keeps your `fields.php` minimal and reuses all standard fields from the toolkit.

## Step 5: Update Frontend Assets

### Update tailwind.config.js

```js
// Before (manual color mappings)
export default {
    theme: {
        extend: {
            colors: {
                primary: '#f97316',  // Hardcoded hex
            },
        },
    },
};

// After (use toolkit preset)
import toolkit from '@tipowerup/ti-theme-toolkit/tailwind-preset';

export default {
    presets: [toolkit],
    content: ['./resources/**/*.blade.php', './resources/src/**/*.{js,ts}'],
    theme: {
        extend: {
            // Only add theme-specific overrides
        },
    },
};
```

### Update vite.config.js

```js
// Before (manual Vite config)
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [laravel(/* ... */)],
    build: { /* ... */ },
});

// After (use toolkit preset)
import { defineConfig } from 'vite';
import { toolkitPreset } from '@tipowerup/ti-theme-toolkit/vite-preset';

export default defineConfig({
    ...toolkitPreset({
        input: ['resources/src/css/app.css', 'resources/src/js/app.js'],
    }),
});
```

### Update resources/src/css/app.css

```css
/* Before */
/* Manual @tailwind directives and custom CSS */

/* After — import toolkit tokens first */
@import '@tipowerup/ti-theme-toolkit/css/tokens.css';
@tailwind base;
@tailwind components;
@tailwind utilities;

/* Your theme-specific styles */
```

### Update resources/src/js/app.js

```js
// Before
// Manual dark mode, color handling, etc.

// After — import toolkit dark-mode store
import '@tipowerup/ti-theme-toolkit/js/dark-mode';

// Your theme-specific JS
```

### Update package.json

Add the toolkit as a dependency:

```json
{
    "devDependencies": {
        "@tipowerup/ti-theme-toolkit": "file:../ti-theme-toolkit"
    }
}
```

(Use `file:` protocol for local development; use npm version once published.)

```bash
npm install
```

## Step 6: Clean Up and Test

```bash
# Clear PHP caches
php artisan view:clear
php artisan config:clear

# Rebuild assets
npm run build

# Publish to TastyIgniter
php artisan igniter:theme-vendor-publish --force

# Test in the browser
# Load the home page, check that colors/dark mode/banners work
```

## Step 7: Update Views (if needed)

If your layouts reference theme data, you may need minor updates to use the new variable names:

```blade
<!-- Before — manually constructed color style -->
<style>
    :root {
        --primary: {{ $themeSettings['color']['primary'] ?? '#000' }};
    }
</style>

<!-- After — toolkit handles this via $themeBrandStyle -->
<html style="{{ $themeBrandStyle }}">
```

## Step 8: Update Language Strings

The abstract provider injects a new translation key for page security labels. Add to your `resources/lang/en/default.php`:

```php
<?php

return [
    // Existing strings...
    
    // Page security (new)
    'label_security' => 'Page Security',
    'text_all' => 'All',
    'text_customer' => 'Customers Only',
    'text_guest' => 'Guests Only',
    'help_security' => 'Control who can access this page',
];
```

## Verification

Run the following checks:

1. **Service Provider boots cleanly:**
   ```bash
   php artisan tinker --execute="app('TiPowerUp\\ThemeToolkit\\AbstractThemeServiceProvider')" | echo OK
   ```

2. **Theme renders without errors:**
   Navigate to the home page in your browser. Check the page source for:
   - `<html style="--color-primary:...">` (inline styles)
   - No console errors

3. **BannerManager widget appears:**
   Go to **Design → Themes → Customize → Banners tab**. The widget should load.

4. **Dark mode works:**
   Toggle dark mode in the navbar. CSS vars should switch, background colors should invert.

5. **Colors update without rebuild:**
   Change `color[primary]` in the theme customizer. Reload the page (no rebuild needed) — brand colors should update.

6. **Tests pass:**
   ```bash
   php artisan test --compact
   ```

## Rollback (if needed)

If something breaks, roll back the toolkit dependency:

```bash
composer remove tipowerup/ti-theme-toolkit
git checkout src/ServiceProvider.php resources/meta/fields.php
php artisan view:clear
```

## Troubleshooting

### "Class not found" errors

Ensure `composer dump-autoload` was run after adding the toolkit.

### "Unknown view" errors

Check that the toolkit's view namespace (`tipowerup.theme-toolkit`) is registered. The abstract provider should do this automatically in `boot()`.

### BannerManager widget shows "Partial not found"

Verify that `loadViewsFrom('...', 'tipowerup.theme-toolkit')` is being called in the abstract provider. If the error persists, check that the toolkit's views directory exists.

### Colors not applying

Ensure `$themeBrandStyle` is rendered on the `<html>` tag:

```blade
<html style="{{ $themeBrandStyle }}">
```

### Dark mode localStorage conflicts

If dark mode state seems wrong, clear it and restart:

```js
localStorage.removeItem('darkMode');
location.reload();
```

## Next Steps

- [Customize colors](color-system.md)
- [Use BannerManager](form-widgets/banner-manager.md)
- [Full API reference](service-provider.md)
