# Creating a New Theme

This guide walks you through building a brand-new TastyIgniter theme from scratch using the toolkit.

## Prerequisites

- TastyIgniter 4.0+ installed and running
- PHP 8.2+, Node 18+
- Basic understanding of Laravel service providers and Tailwind CSS

## Step 1: Scaffold the Theme Package

Create a new directory alongside your TastyIgniter installation:

```bash
mkdir vendor/myvendor/ti-theme-myname
cd vendor/myvendor/ti-theme-myname
git init
```

### Directory Structure

```
vendor/myvendor/ti-theme-myname/
├── src/
│   ├── ServiceProvider.php
│   ├── Livewire/              # (optional) Livewire components
│   └── View/Components/       # (optional) Blade view components
├── resources/
│   ├── lang/
│   │   └── en/
│   │       └── default.php
│   ├── meta/
│   │   ├── assets.json
│   │   ├── fields.php
│   │   └── theme.php
│   ├── views/
│   │   ├── _layouts/
│   │   │   └── default.blade.php
│   │   ├── _pages/
│   │   │   └── home.blade.php
│   │   ├── includes/
│   │   └── errors/
│   └── src/
│       ├── css/
│       │   └── app.css
│       └── js/
│           └── app.js
├── public/                    # Built assets (gitignored)
│   ├── css/
│   ├── js/
│   └── assets/
├── composer.json
├── package.json
├── vite.config.js
├── tailwind.config.js
└── README.md
```

## Step 2: composer.json

```json
{
    "name": "myvendor/ti-theme-myname",
    "description": "My custom TastyIgniter theme",
    "type": "tastyigniter-extension",
    "license": "MIT",
    "require": {
        "php": "^8.2",
        "tastyigniter/core": "^4.0",
        "tipowerup/ti-theme-toolkit": "^0.1"
    },
    "autoload": {
        "psr-4": {
            "MyVendor\\MyTheme\\": "src/"
        }
    },
    "extra": {
        "tastyigniter-extension": {
            "code": "myvendor-myname",
            "title": "My Theme",
            "description": "A custom theme using TiPowerUp Toolkit",
            "version": "1.0.0",
            "author": "My Name",
            "providers": [
                "MyVendor\\MyTheme\\ServiceProvider"
            ]
        }
    }
}
```

## Step 3: package.json

```json
{
    "name": "@myvendor/ti-theme-myname",
    "version": "1.0.0",
    "type": "module",
    "scripts": {
        "dev": "vite",
        "build": "vite build",
        "watch": "vite build --watch"
    },
    "dependencies": {
        "alpinejs": "^3.14",
        "tailwindcss": "^3.4"
    },
    "devDependencies": {
        "@tipowerup/ti-theme-toolkit": "file:../ti-theme-toolkit",
        "@tailwindcss/forms": "^0.5",
        "laravel-vite-plugin": "^1.0",
        "postcss": "^8.4",
        "vite": "^5.0"
    }
}
```

## Step 4: ServiceProvider.php

Create `src/ServiceProvider.php`:

```php
<?php

declare(strict_types=1);

namespace MyVendor\MyTheme;

use TiPowerUp\ThemeToolkit\AbstractThemeServiceProvider;

class ServiceProvider extends AbstractThemeServiceProvider
{
    protected function themeCode(): string
    {
        return 'myvendor-myname';
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
     * Override to add custom routes specific to your theme.
     *
     * @return array<int, array{0: string, 1: string, 2: class-string, 3: string}>
     */
    protected function routes(): array
    {
        return [];
    }
}
```

## Step 5: resources/meta/fields.php

Create the theme settings form using the toolkit's base schema:

```php
<?php

use TiPowerUp\ThemeToolkit\Fields\BaseSchema;

return [
    'form' => BaseSchema::merge(
        ['tabs' => BaseSchema::tabs()],
        [
            'tabs' => [
                'colors' => [
                    'fields' => [
                        'color[primary]' => [
                            'default' => '#3b82f6',  // Your brand color
                        ],
                    ],
                ],
            ],
        ]
    )['tabs'],
];
```

## Step 6: resources/meta/theme.php

```php
<?php

return [
    'title' => 'My Theme',
    'description' => 'A custom theme using TiPowerUp Toolkit',
    'author' => 'Your Name',
    'version' => '1.0.0',
];
```

## Step 7: resources/meta/assets.json

```json
{
    "css": "css/app.css",
    "js": "js/app.js"
}
```

## Step 8: tailwind.config.js

```js
import toolkit from '@tipowerup/ti-theme-toolkit/tailwind-preset';

export default {
    presets: [toolkit],
    content: [
        './resources/**/*.blade.php',
        './resources/src/**/*.{js,ts}',
    ],
    theme: {
        extend: {
            // Add theme-specific tokens here
        },
    },
};
```

## Step 9: vite.config.js

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

## Step 10: resources/src/css/app.css

```css
@import '@tipowerup/ti-theme-toolkit/css/tokens.css';
@tailwind base;
@tailwind components;
@tailwind utilities;

/* Theme-specific styles */
```

## Step 11: resources/src/js/app.js

```js
import '@tipowerup/ti-theme-toolkit/js/dark-mode';

// Your theme-specific JavaScript
```

## Step 12: resources/lang/en/default.php

```php
<?php

return [
    'label_security' => 'Page Security',
    'text_all' => 'All',
    'text_customer' => 'Customers Only',
    'text_guest' => 'Guests Only',
    'help_security' => 'Restrict access to this page',
];
```

## Step 13: Minimal Blade Layout

Create `resources/views/_layouts/default.blade.php`:

```blade
<!DOCTYPE html>
<html lang="en" {!! $themeBrandStyle ? 'style="' . $themeBrandStyle . '"' : '' !!}>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('tipowerup.theme-toolkit::_partials.dark-mode-head')
    <title>{{ $page->title ?? config('app.name') }}</title>
    @vite(['resources/src/css/app.css', 'resources/src/js/app.js'])
</head>
<body class="bg-body text-text">
    {{ $slot }}

    @vite('resources/src/js/app.js')
</body>
</html>
```

Create `resources/views/_pages/home.blade.php`:

```blade
<x-layouts.default>
    <div class="container mx-auto py-12">
        <h1 class="text-3xl font-bold text-primary">Welcome to My Theme</h1>
    </div>
</x-layouts.default>
```

## Step 14: Build & Install

From the TastyIgniter project root:

```bash
# Install dependencies
cd vendor/myvendor/ti-theme-myname
npm install
composer dump-autoload

# Build assets
npm run build

# Publish to TastyIgniter
cd /path/to/tastyigniter
php artisan igniter:theme-vendor-publish --force
php artisan view:clear
```

## Step 15: Register the Theme

In the TastyIgniter admin:
1. Go to **Design** → **Themes**
2. Click **Manage**
3. Your theme should appear in the list
4. Click **Preview** to test

## Troubleshooting

### "Call to undefined method" errors
Ensure `composer dump-autoload` was run after creating the ServiceProvider.

### BannerManager widget not showing
Run `php artisan view:clear` and check that the toolkit's views are loaded. The widget requires the `bannermanager` field type in your `fields.php`.

### Dark mode not persisting
Verify that `dark-mode.js` was imported in `resources/src/js/app.js` and that `npm run build` completed successfully.

### Flash of wrong theme on initial page load
The `@include('tipowerup.theme-toolkit::_partials.dark-mode-head')` line must sit in `<head>` **before** `@vite(...)`. If it's after the stylesheet or missing entirely, you'll see a brief light-mode flash before Alpine applies the dark class.

### Brand colors not applying
Check that `<html>` has the inline `style` attribute. Load the home page source and look for `--color-primary`. If missing, run `php artisan view:clear` and reload.

### Build fails with "module not found"
Ensure `@tipowerup/ti-theme-toolkit` is in `package.json` dependencies (use `file:` protocol for local development).

## Next Steps

- [Customize the color system](../color-system.md)
- [Use the BannerManager widget](../form-widgets/banner-manager.md)
- [Dark mode API reference](../dark-mode.md)
- [Migrate an existing theme](../migration-from-v0.md)
