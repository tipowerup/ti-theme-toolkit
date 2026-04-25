<?php

declare(strict_types=1);

namespace TiPowerUp\ThemeToolkit;

use Illuminate\Support\ServiceProvider;
use TiPowerUp\ThemeToolkit\FormWidgets\BannerManager;
use TiPowerUp\ThemeToolkit\Support\ThemePayloadResolver;

/**
 * ToolkitServiceProvider is the package-level service provider auto-discovered
 * by Laravel. It registers toolkit-wide singletons and the `tipowerup.theme-toolkit`
 * view namespace (used by BannerManager partials). It does NOT register the
 * BannerManager form widget — that is done by `AbstractThemeServiceProvider`
 * so each child theme controls whether it opts in.
 *
 * Child themes should NOT register this provider themselves — it is
 * auto-discovered via `extra.laravel.providers` in `composer.json`.
 */
class ToolkitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ThemePayloadResolver::class);
    }

    public function boot(): void
    {
        // Register the toolkit's own view namespace so BannerManager partials
        // can always be resolved via `tipowerup.theme-toolkit::_partials.*`
        // regardless of which child theme is active.
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'tipowerup.theme-toolkit');
    }
}
