# AbstractThemeServiceProvider Reference

The base service provider that all TiPowerUp themes extend. It handles view loading, translations, Livewire components, Blade components, routing, page authentication, and theme data composition.

## Abstract Methods (child theme must implement)

### themeCode() : string

The TastyIgniter theme code identifier.

```php
protected function themeCode(): string
{
    return 'tipowerup-orange-tw';
}
```

Used as the view namespace and in route prefixes.

### phpNamespace() : string

The root PHP namespace for theme classes (PSR-4).

```php
protected function phpNamespace(): string
{
    return 'TiPowerUp\\OrangeTw';
}
```

Used to locate Livewire components, Blade components, and form widgets via the FQCN-to-directory scanner.

### viewsPath() : string

Absolute path to the theme's Blade view directory.

```php
protected function viewsPath(): string
{
    return __DIR__.'/../resources/views';
}
```

Required. Views are registered with the namespace returned by `viewNamespace()`.

### langPath() : string

Absolute path to the theme's language directory.

```php
protected function langPath(): string
{
    return __DIR__.'/../resources/lang';
}
```

Required. Translations loaded with the namespace returned by `translationNamespace()`.

### livewirePath() : string

Absolute path to the theme's Livewire component directory.

```php
protected function livewirePath(): string
{
    return __DIR__.'/Livewire';
}
```

The scanner looks for `*.php` files and excludes the `Concerns/`, `Features/`, and `Forms/` subdirectories. Forms helper classes (Livewire `Form` subclasses living in `Forms/`) aren't registered as full components.

### bladeComponentsPath() : string

Absolute path to the theme's Blade view-component directory.

```php
protected function bladeComponentsPath(): string
{
    return __DIR__.'/View/Components';
}
```

The scanner looks for `*.php` files and auto-registers them.

## Virtual Getters (concrete defaults, overridable)

### viewNamespace() : string

The view namespace for theme templates. Defaults to `themeCode()`.

```php
protected function viewNamespace(): string
{
    return $this->themeCode();  // 'tipowerup-orange-tw'
}
```

Override if your views should be referenced under a different namespace.

### translationNamespace() : string

The translation namespace key. Defaults to a convention-derived name: `{vendor}.{rest}` where the first hyphen-delimited segment is the vendor.

```php
protected function translationNamespace(): string
{
    // 'tipowerup-orange-tw' → 'tipowerup.orange-tw'
    // 'tipowerup-orange'    → 'tipowerup.orange'
}
```

Used in translation keys and field labels.

### routePrefix() : string

Route name prefix. Defaults to `translationNamespace()` + `'.'`.

```php
protected function routePrefix(): string
{
    return $this->translationNamespace().'.';  // 'tipowerup.orange-tw.'
}
```

All routes from `routes()` are prefixed with this.

### routes() : array

Theme-specific routes. Defaults to `[]` (no routes).

```php
protected function routes(): array
{
    return [
        ['get', 'logout', LogoutController::class, 'account.logout'],
    ];
}
```

Each entry is `[method, uri, action, name]`. Routes are automatically prefixed with `routePrefix()` and Igniter's base URI. See [orange-tw example](../src/ServiceProvider.php).

## Boot Sequence

The `boot()` method runs in this order:

1. **registerFormWidgets()** — Registers BannerManager widget with code `bannermanager`.
2. **loadViewsFrom()** — Loads views from `viewsPath()` under `viewNamespace()`.
3. **loadTranslationsFrom()** — Loads translations under `translationNamespace()`.
4. **loadBladeComponentsFrom()** — Scans and registers Blade components.
5. **loadLivewireComponentsFrom()** — Scans and registers Livewire components.
6. **Layout namespace** — Aliases `layouts` to `viewsPath()/_layouts`.
7. **Wildcard View composer** — Shares theme payload, branding, and Livewire config to all views.
8. **View composers** — Registers composers for contact info, EU cookie banner, social buttons, and mobile menu.
9. **configureLivewire()** — Sets persistent middleware and pagination theme.
10. **configurePageAuthentication()** — Binds theme payload injection, page security checks, and admin form extensions.
11. **configureGoogleFonts()** — Configures Google Fonts from theme settings.
12. **defineRoutes()** — Registers theme routes from `routes()`.

## View Composer Data

The wildcard composer shares these variables with **all non-admin views**:

| Variable | Type | Description |
|----------|------|-------------|
| `$theme` | Theme | The active TastyIgniter theme object |
| `$page` | Page | The current page being rendered |
| `$site_logo` | string | Logo URL from site settings |
| `$site_name` | string | Site name from settings |
| `$themeConfig` | array | Theme configuration (empty if not set) |
| `$themeData` | array | Resolved theme custom data (colors, banners, etc.) |
| `$themeBrandStyle` | string | CSS inline style string for `<html>` |

The composer also sets `config('livewire.navigate.progress_bar_color')` from the resolved primary color.

## Example: Child Theme Implementation

```php
<?php

declare(strict_types=1);

namespace MyVendor\MyTheme;

use MyVendor\MyTheme\Http\Controllers\Logout;
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

    protected function routes(): array
    {
        return [
            ['get', 'logout', Logout::class, 'account.logout'],
        ];
    }
}
```

That's it. Everything else is inherited and auto-wired by the abstract provider.

## Page Security

If a page has the `security` setting in admin:
- **all** (default) — No restriction, guest and customer access allowed.
- **customer** — Redirect unauthenticated users to home.
- **guest** — Redirect authenticated users to home.

The check is bound to the `page.init` event and happens before the page renders.

## Livewire Persistent Middleware

The abstract provider registers:
- `CheckLocation` — Validates the current location (local delivery area).
- `CartMiddleware` — Maintains cart session across requests.

These are added to Livewire as persistent middleware so they survive component updates.

## Component Auto-Discovery

### Livewire Components

The auto-loader scans **two paths in order** and registers everything under the active theme's `viewNamespace()`:

1. **Toolkit-shipped components** (`<toolkit-root>/src/Livewire/`) — auth flow (`Login`, `Register`, `ResetPassword`, `Socialite`), `Contact`, `NewsletterSubscribeForm`, etc. Their `componentMeta()` uses `{ns}` and `{lang}` placeholders that the loader substitutes with the active theme's view and translation namespaces. Their `render()` resolves `view($this->resolveViewNamespace().'::livewire.<name>')` at runtime so each theme's blade view wins.
2. **Theme components** (`livewirePath()`) — the theme's own `src/Livewire/` directory. Livewire registration is **last-writer-wins**, so a theme can drop a class with the same relative path (e.g. `src/Livewire/Login.php`) extending the toolkit's class to add custom behaviour, or replacing it entirely.

Filter rules for both passes:
- Pattern: `*.php` (subdirectories `Concerns/`, `Features/`, `Forms/` excluded)
- **ConfigurableComponent trait**: Registered with TastyIgniter ComponentManager via `componentMeta()` (placeholders resolved).
- **Regular Livewire**: Registered with Livewire under `{viewNamespace()}::{kebab-name}`.

#### Container binding

`AbstractThemeServiceProvider::boot()` binds the active theme's view namespace as `tipowerup.theme.viewNamespace`. Toolkit Livewire components use it as a fallback when `controller()` returns null (e.g. under testbench or Livewire test runners) — `controller()?->getTheme()?->getName() ?? app('tipowerup.theme.viewNamespace')`.

### Blade Components

Files in `bladeComponentsPath()`:
- Pattern: `*.php`
- **ConfigurableComponent trait**: Registered with TastyIgniter ComponentManager.
- **Regular Blade**: Registered with Blade under `{viewNamespace()}::{kebab-name}`.

Example:
- File: `src/Livewire/AccountSettings.php` → Alias: `myvendor-myname::account-settings`
- File: `src/View/Components/Card.php` → Alias: `myvendor-myname::card`

## Customization Points

To extend the abstract provider in your theme:

```php
class ServiceProvider extends AbstractThemeServiceProvider
{
    // ... implement abstract methods ...

    // Override a virtual getter
    protected function translationNamespace(): string
    {
        return 'my.custom.key';
    }

    // Extend the boot method
    public function boot(): void
    {
        parent::boot();

        // Your custom boot logic here
    }
}
```

## Related Documentation

- [Creating a new theme](getting-started.md)
- [BaseSchema and fields composition](fields-schema.md)
- [Color system and palette derivation](color-system.md)
