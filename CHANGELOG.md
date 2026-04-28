# Changelog

All notable changes to `tipowerup/ti-theme-toolkit` are documented here. Format
follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the
project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [0.4.0] - 2026-04-28

### Added

- **Auto-publish theme assets on activation** — new
  `AbstractThemeServiceProvider::registerAutoVendorPublish()` listens on
  `main.theme.activated` and runs `igniter:theme-vendor-publish --theme=<code> --force`
  in-process when the theme matching the SP's `themeCode()` is activated.
  Closes the gap where TastyIgniter only auto-publishes during
  `igniter:install` — themes installed and activated afterwards no longer
  ship with missing favicons / fallback logos / static assets until an
  admin remembers to run the command. Listener is scoped to the active
  theme (so other toolkit-using themes don't cross-publish), skipped under
  `runningUnitTests()`, and wraps the artisan call in a try/catch — a
  failed publish logs a warning instead of breaking activation.

## [0.3.0] - 2026-04-28

Two unrelated lines of work shipped together in this minor bump.

### Tailwind v4 migration + TypeScript declarations

#### Added

- **Hand-written `.d.ts` companions** for `vite-preset` and `dark-mode`
  (same pattern Vite and Tailwind use). No build step required — full
  IntelliSense for consuming themes, with TS opt-in detected automatically.
- **Auto-detection of `app.ts` over `app.js`** in the Vite preset so themes
  can switch to TypeScript without changing config.
- **Neutral-colour overrides** in `ColorHelper` / `ThemePayloadResolver`
  with a light-mode-scoped `<style>` block for tokens that can't ride on
  the `<html>` style attribute.
- **`themeBrandStyle` and `themeNeutralStyle`** exposed on the view
  composer, plus bound to TI's controller var bag for non-Livewire page
  renders.

#### Changed

- **Themes consume `theme.css` directly via `@import`.** No more PostCSS
  / JS preset layer.
- **`vite-preset.js` switches to `@tailwindcss/vite`** with deterministic
  output paths.
- **`AbstractThemeServiceProvider::phpNamespace()` defaults via
  reflection** of the concrete child SP. Themes only need to override if
  their classes live under a different namespace from the SP itself.

#### Removed

- **`tailwind-preset.js`** — Tailwind v4 doesn't need it.
- **`resources/src/css/tokens.css`** — superseded by `theme.css`.

### Shared Livewire components + auto-loader rework

#### Added

- **Shared Livewire components.** Auth flow (`Login`, `Register`,
  `ResetPassword`, `Socialite`) plus `Contact` and `NewsletterSubscribeForm`
  now live in the toolkit. Themes only own the corresponding blade views;
  business logic is single-source.
- **Shared form classes.** `Forms\LoginForm` and `Forms\RegisterForm`.
- **Auto-loader: dual-path scanning.** `loadLivewireComponentsFrom()` now
  accepts an optional `$livewireNamespace` argument. The toolkit scans its
  own `src/Livewire/` first under the active theme's view namespace, then
  the theme's `src/Livewire/`. Livewire registration is last-writer-wins,
  so themes can override any toolkit component by dropping a class with the
  same relative path.
- **`{ns}` / `{lang}` placeholder substitution in `componentMeta()`.**
  Toolkit Livewire classes return placeholder-formatted descriptors
  (e.g. `'{ns}::login'`, `'{lang}::default.component_login_title'`); the
  auto-loader's new `resolveComponentMeta()` substitutes them with the
  active theme's `viewNamespace()` and `translationNamespace()` at
  registration time.
- **Container binding `tipowerup.theme.viewNamespace`.** Bound at SP boot;
  toolkit Livewire components fall back to it when `controller()` is null
  (e.g. under testbench / Livewire tests).
- **Static-page menu URL fix** as a workaround for upstream bug
  ([tastyigniter/ti-ext-pages#21](https://github.com/tastyigniter/ti-ext-pages/pull/21)).
  `registerStaticPageResolverPatch()` registers a corrective
  `pages.menuitem.resolveItem` listener so static-page menu items resolve
  to the page matching their reference id instead of the alphabetically-
  first cached page. Becomes a redundant override once upstream merges;
  marked with a `TODO: remove once #21 is merged`.
- **Test suite.** Pest 4 + testbench. 12 tests covering placeholder
  substitution, listener registration, view-namespace binding, Livewire
  component registration, and the `*Form.php` filter regression.

#### Changed

- **`AbstractThemeServiceProvider::loadLivewireComponentsFrom()` signature.**
  Now accepts `?string $livewireNamespace = null` as a second argument
  (defaults to `phpNamespace().'\\Livewire\\'`). Existing callers are
  unaffected.
- **`AbstractThemeServiceProvider::loadLivewireComponentsFrom()` filename
  filter.** Removed the overly-broad `notName('*Form.php')` rule, which
  was excluding real Livewire components whose class name ends in `Form`
  (e.g. `NewsletterSubscribeForm`). Forms helper classes are still
  excluded via `notPath('Forms')`.

#### Removed

- **`AbstractThemeServiceProvider::registerMobileMenuViewComposer()`.** The
  composer was hardcoded to query `main-menu` and unconditionally override
  `$menuItems` for the `includes.navs.mobile-menu` partial, clobbering items
  passed by the `<x-nav>` component. Themes that need a `mobile-menu` now
  seed it via `resources/meta/menus/mobile-menu.php` and the `Nav` view
  component owns the data flow.

## [Earlier]

History prior to this release lives in `git log`.
