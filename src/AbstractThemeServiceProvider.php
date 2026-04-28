<?php

declare(strict_types=1);

namespace TiPowerUp\ThemeToolkit;

use Igniter\Admin\Classes\Widgets;
use Igniter\Admin\Widgets\Form;
use Igniter\Cart\Http\Middleware\CartMiddleware;
use Igniter\Flame\Support\Facades\Igniter;
use Igniter\Local\Http\Middleware\CheckLocation;
use Igniter\Local\Models\Location;
use Igniter\Main\Classes\MainController;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Template\Page;
use Igniter\Main\Traits\ConfigurableComponent;
use Igniter\System\Classes\ComponentManager;
use Igniter\User\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Livewire;
use Spatie\GoogleFonts\GoogleFonts;
use Symfony\Component\Finder\Finder;
use TiPowerUp\ThemeToolkit\FormWidgets\BannerManager;
use TiPowerUp\ThemeToolkit\Livewire\Features\SupportFlashMessages;
use TiPowerUp\ThemeToolkit\Support\ThemePayloadResolver;

/**
 * AbstractThemeServiceProvider is the base service provider all TiPowerUp
 * themes should extend. It wires up views, translations, Livewire, Blade
 * components, routing, page authentication, Google Fonts, and the brand-color
 * view composer using a small set of abstract getters that the child theme
 * implements.
 *
 * Minimal child-theme service provider — only `themeCode()` is required.
 * `phpNamespace()` defaults to the SP's own namespace (via reflection) and
 * paths default to the standard theme layout:
 * <code>
 * class ServiceProvider extends AbstractThemeServiceProvider
 * {
 *     protected function themeCode(): string { return 'my-theme'; }
 * }
 * </code>
 *
 * Override `phpNamespace()`, `viewsPath()`, `langPath()`, `livewirePath()`,
 * or `bladeComponentsPath()` if your theme uses a non-standard layout.
 */
abstract class AbstractThemeServiceProvider extends ServiceProvider
{
    // -------------------------------------------------------------------------
    // Abstract getters — child theme MUST implement these
    // -------------------------------------------------------------------------

    /**
     * The TastyIgniter theme code, e.g. `'tipowerup-orange-tw'`.
     */
    abstract protected function themeCode(): string;

    // -------------------------------------------------------------------------
    // Virtual getters — concrete defaults, overridable by child theme
    // -------------------------------------------------------------------------

    /**
     * Root PHP namespace for the theme's classes, e.g. `'TiPowerUp\\OrangeTw'`.
     *
     * Defaults to the namespace of the concrete child SP (resolved via
     * reflection), since by convention the theme's classes share that
     * namespace. Override only if the theme's Livewire / Blade components
     * live under a different namespace from the SP itself.
     */
    protected function phpNamespace(): string
    {
        return (new \ReflectionClass(static::class))->getNamespaceName();
    }

    /**
     * Absolute path to the theme's Blade view directory.
     * Defaults to `<package-root>/resources/views`.
     */
    protected function viewsPath(): string
    {
        return $this->packageRoot().'/resources/views';
    }

    /**
     * Absolute path to the theme's language directory.
     * Defaults to `<package-root>/resources/lang`.
     */
    protected function langPath(): string
    {
        return $this->packageRoot().'/resources/lang';
    }

    /**
     * Absolute path to the theme's Livewire component directory.
     * Defaults to `<package-root>/src/Livewire`.
     */
    protected function livewirePath(): string
    {
        return $this->packageRoot().'/src/Livewire';
    }

    /**
     * Absolute path to the theme's Blade view-component directory.
     * Defaults to `<package-root>/src/View/Components`.
     */
    protected function bladeComponentsPath(): string
    {
        return $this->packageRoot().'/src/View/Components';
    }

    /**
     * Resolve the package root by reflecting on the concrete child provider
     * class, taking its file's directory and walking up one level (since
     * convention places the child SP at `<package-root>/src/ServiceProvider.php`).
     *
     * Override this single method if the SP lives elsewhere — every default
     * path getter then adjusts automatically.
     */
    protected function packageRoot(): string
    {
        $reflector = new \ReflectionClass(static::class);

        return dirname((string) $reflector->getFileName(), 2);
    }

    /**
     * The view namespace registered for the theme's Blade templates.
     * Defaults to the theme code (e.g. `'tipowerup-orange-tw'`).
     */
    protected function viewNamespace(): string
    {
        return $this->themeCode();
    }

    /**
     * The translation namespace key, derived from the theme code by the
     * `{vendor}.{rest}` convention: the first hyphen-delimited segment is the
     * vendor, everything after it is the rest (hyphens preserved).
     *
     * - `'tipowerup-orange-tw'` → `'tipowerup.orange-tw'`
     * - `'tipowerup-orange'`    → `'tipowerup.orange'`
     * - `'igniter-orange'`      → `'igniter.orange'`
     */
    protected function translationNamespace(): string
    {
        $code = $this->themeCode();
        $vendor = Str::before($code, '-');
        $rest = Str::after($code, '-');

        return $rest === $code ? $code : $vendor.'.'.$rest;
    }

    /**
     * Named-route prefix. Defaults to `'{translationNamespace}.'`.
     * For `'tipowerup.orange-tw'` → `'tipowerup.orange-tw.'`.
     */
    protected function routePrefix(): string
    {
        return $this->translationNamespace().'.';
    }

    /**
     * Route definitions the child theme wants to register. Return an array of
     * `[method, uri, action, name]` tuples; the abstract provider wraps them
     * with `$this->routePrefix()` and Igniter's base URI.
     *
     * Override in the child theme to add theme-specific routes:
     * <code>
     * protected function routes(): array
     * {
     *     return [
     *         ['get', 'logout', Logout::class, 'account.logout'],
     *     ];
     * }
     * </code>
     *
     * @return array<int, array{0: string, 1: string, 2: class-string, 3: string}>
     */
    protected function routes(): array
    {
        return [];
    }

    // -------------------------------------------------------------------------
    // Service provider lifecycle
    // -------------------------------------------------------------------------

    public function register(): void
    {
        // ThemePayloadResolver is registered by ToolkitServiceProvider.
        if (! $this->app->runningUnitTests()) {
            Livewire::componentHook(SupportFlashMessages::class);
        }
    }

    public function boot(): void
    {
        $this->registerFormWidgets();

        // Cache the active theme's view namespace in the container so toolkit
        // Livewire components (which can't extend this SP) can resolve it at
        // render time without needing a TI MainController — falls back here
        // when running under testbench / Livewire tests where `controller()`
        // is null.
        $this->app->instance('tipowerup.theme.viewNamespace', $this->viewNamespace());

        $this->loadViewsFrom($this->viewsPath(), $this->viewNamespace());
        $this->loadTranslationsFrom($this->langPath(), $this->translationNamespace());
        $this->loadBladeComponentsFrom($this->bladeComponentsPath());

        // Toolkit-shipped Livewire components (auth flow, etc.) register first
        // under the active theme's view namespace. Then the theme's own
        // src/Livewire/ scan runs — Livewire registration is last-writer-wins,
        // so any class a theme provides under the same name overrides the
        // toolkit default (subclass to inherit + customise, or replace fully).
        $this->loadLivewireComponentsFrom(__DIR__.'/Livewire', 'TiPowerUp\\ThemeToolkit\\Livewire\\');
        $this->loadLivewireComponentsFrom($this->livewirePath());

        // Define layout aliases.
        $this->app['view']->addNamespace('layouts', $this->viewsPath().'/_layouts');

        // Single wildcard composer: share theme payload + tweak Livewire config.
        ViewFacade::composer('*', function (View $view): void {
            if (Igniter::runningInAdmin() || ! controller()) {
                return;
            }

            $payload = resolve(ThemePayloadResolver::class)->resolve();

            $view->with([
                'theme' => controller()->getTheme(),
                'page' => controller()->getPage(),
                'site_logo' => setting('site_logo'),
                'site_name' => setting('site_name'),
                'themeConfig' => controller()->getTheme()?->config ?? [],
                'themeData' => $payload['themeData'],
                'themeBrandStyle' => $payload['themeBrandStyle'],
                'themeNeutralStyle' => $payload['themeNeutralStyle'],
            ]);

            if ($payload['primary']) {
                config()->set('livewire.navigate.progress_bar_color', $payload['primary']);
            }
        });

        $this->registerContactViewComposers();
        $this->registerEuCookieBannerViewComposer();
        $this->registerSocialButtonsViewComposer();
        $this->registerStaticPageResolverPatch();
        $this->configureLivewire();
        $this->configurePageAuthentication();
        $this->configureGoogleFonts();
        $this->defineRoutes();
    }

    // -------------------------------------------------------------------------
    // View composers
    // -------------------------------------------------------------------------

    /**
     * Share the default location with contact-related partials.
     */
    protected function registerContactViewComposers(): void
    {
        $views = [
            "{$this->viewNamespace()}::includes.contact.info",
            "{$this->viewNamespace()}::includes.contact.hours",
        ];

        ViewFacade::composer($views, function (View $view): void {
            $location = Location::getDefault();
            $view->with('defaultLocation', $location);
        });
    }

    /**
     * Share GDPR cookie-banner settings with the EU cookie partial.
     */
    protected function registerEuCookieBannerViewComposer(): void
    {
        ViewFacade::composer("{$this->viewNamespace()}::includes.eucookiebanner", function (View $view): void {
            $gdpr = resolve(ThemePayloadResolver::class)->resolve()['themeData']['gdpr'] ?? [];

            $privacyPage = ! empty($gdpr['more_info_link'])
                ? \Igniter\Pages\Models\Page::find($gdpr['more_info_link'])
                : null;

            $view->with([
                'gdprEnabled' => (bool) ($gdpr['enabled'] ?? false),
                'privacyPage' => $privacyPage,
                'cookieMessage' => $gdpr['message']
                    ?? 'We use cookies to enhance your browsing experience, serve personalized content, and analyze our traffic. By clicking "Accept All", you consent to our use of cookies.',
                'moreInfoText' => $gdpr['more_info_text'] ?? 'Learn more',
                'acceptText' => $gdpr['accept_text'] ?? 'Accept All',
                'declineText' => $gdpr['decline_text'] ?? 'Decline',
            ]);
        });
    }

    /**
     * Share social-provider links with the social buttons partial.
     */
    protected function registerSocialButtonsViewComposer(): void
    {
        ViewFacade::composer("{$this->viewNamespace()}::includes.auth.social-buttons", function (View $view): void {
            $socialLinks = [];
            $successPage = 'account/login';
            $errorPage = 'account/login';

            try {
                $socialiteComponent = app('Igniter\Socialite\Components\Socialite');
                if (method_exists($socialiteComponent, 'getProviderLinks')) {
                    $socialLinks = $socialiteComponent->getProviderLinks();
                }
                $successPage = $socialiteComponent->property('successPage', 'account/login');
                $errorPage = $socialiteComponent->property('errorPage', 'account/login');
            } catch (\Throwable) {
                // Socialite extension not installed — leave defaults.
            }

            $view->with([
                'socialLinks' => $socialLinks,
                'successPage' => $successPage,
                'errorPage' => $errorPage,
            ]);
        });
    }

    /**
     * Workaround for an upstream bug in tastyigniter/ti-ext-pages where
     * Igniter\Pages\Classes\Page::resolveMenuItem() filters the cached page
     * collection but discards the filtered result, so every static-page menu
     * item resolves to the alphabetically-first published page.
     *
     * Registers a second listener on `pages.menuitem.resolveItem` that resolves
     * the reference correctly. MenuManager iterates all listener responses and
     * overwrites `url` per response; this listener registers after the buggy
     * core one (theme service providers boot after extensions), so its correct
     * URL wins.
     *
     * TODO: remove once https://github.com/tastyigniter/ti-ext-pages/pull/21
     * is merged and the fix lands in a released version.
     */
    protected function registerStaticPageResolverPatch(): void
    {
        if (! class_exists(\Igniter\Pages\Models\Page::class)) {
            return;
        }

        Event::listen('pages.menuitem.resolveItem', function ($item, string $url, $theme): ?array {
            if (! $theme || $item->type !== 'static-page' || ! $item->reference) {
                return null;
            }

            $page = \Igniter\Pages\Models\Page::whereIsEnabled()
                ->where('page_id', $item->reference)
                ->first();

            if (! $page) {
                return null;
            }

            $pageUrl = URL::to($page->permalink_slug);

            return [
                'url' => $pageUrl,
                'isActive' => rawurldecode($pageUrl) === rawurldecode($url),
            ];
        });
    }

    // -------------------------------------------------------------------------
    // Component loaders
    // -------------------------------------------------------------------------

    /**
     * Scan a directory for Livewire component classes and register them.
     *
     * Components implementing ConfigurableComponent are registered with the
     * TastyIgniter ComponentManager (they declare their own alias via
     * `componentMeta()`). All other Livewire components are registered under
     * `{viewNamespace}::{kebab-name}`.
     */
    protected function loadLivewireComponentsFrom(string|array $path, ?string $livewireNamespace = null): void
    {
        $configurableComponents = [];

        $components = (new Finder)->files()->in($path)
            ->name('*.php')
            ->notPath('Concerns')
            ->notPath('Features')
            ->notPath('Forms')
            ->ignoreDotFiles(true)
            ->ignoreVCS(true);

        $livewireNamespace ??= $this->phpNamespace().'\\Livewire\\';

        foreach ($components as $component) {
            $componentName = Str::of($component->getRelativePathname())
                ->before('.php')
                ->kebab()
                ->replace(DIRECTORY_SEPARATOR.'-', '.');

            $componentClass = Str::of($component->getRelativePathname())
                ->before('.php')
                ->replace('/', '\\')
                ->start($livewireNamespace)
                ->toString();

            if (is_subclass_of($componentClass, Component::class)) {
                if (in_array(ConfigurableComponent::class, class_uses_recursive($componentClass))) {
                    $configurableComponents[] = $componentClass;
                } else {
                    Livewire::component($this->viewNamespace().'::'.$componentName, $componentClass);
                }
            }
        }

        resolve(ComponentManager::class)->registerCallback(function ($manager) use ($configurableComponents): void {
            foreach ($configurableComponents as $componentClass) {
                if (method_exists($componentClass, 'componentMeta')) {
                    $manager->registerComponent($componentClass, $this->resolveComponentMeta($componentClass::componentMeta()));
                }
            }
        });
    }

    /**
     * Substitute `{ns}` and `{lang}` placeholders in a component's meta array
     * with the active theme's view and translation namespaces, so toolkit
     * components register under the right theme-scoped descriptors.
     */
    protected function resolveComponentMeta(array $meta): array
    {
        $replacements = [
            '{ns}' => $this->viewNamespace(),
            '{lang}' => $this->translationNamespace(),
        ];

        foreach (['code', 'name', 'description'] as $key) {
            if (isset($meta[$key]) && is_string($meta[$key])) {
                $meta[$key] = strtr($meta[$key], $replacements);
            }
        }

        return $meta;
    }

    /**
     * Scan a directory for Blade view-component classes and register them.
     *
     * Components implementing ConfigurableComponent are registered with the
     * TastyIgniter ComponentManager. All other Blade components are registered
     * under `{viewNamespace}::{kebab-name}`.
     */
    protected function loadBladeComponentsFrom(string|array $path): void
    {
        $components = (new Finder)->files()->in($path)
            ->name('*.php')
            ->ignoreDotFiles(true)
            ->ignoreVCS(true);

        $bladeNamespace = $this->phpNamespace().'\\View\\Components\\';

        resolve(ComponentManager::class)->registerCallback(function ($manager) use ($components, $bladeNamespace): void {
            foreach ($components as $component) {
                $componentName = Str::of($component->getRelativePathname())
                    ->before('.php')
                    ->kebab()
                    ->replace(DIRECTORY_SEPARATOR.'-', '.');

                $componentClass = Str::of($component->getRelativePathname())
                    ->before('.php')
                    ->replace('/', '\\')
                    ->start($bladeNamespace)
                    ->toString();

                if (in_array(ConfigurableComponent::class, class_uses_recursive($componentClass))) {
                    $manager->registerComponent($componentClass, $componentClass::componentMeta());
                } else {
                    Blade::component($this->viewNamespace().'::'.$componentName, $componentClass);
                }
            }
        });
    }

    // -------------------------------------------------------------------------
    // Configuration helpers
    // -------------------------------------------------------------------------

    /**
     * Configure page-level authentication guards and inject theme payload into
     * Pagic-rendered layouts (which bypass Laravel's View composers).
     */
    protected function configurePageAuthentication(): void
    {
        if (! Igniter::runningInAdmin()) {
            MainController::extend(function ($controller): void {
                // Share theme-derived view data directly on the controller so it
                // reaches Pagic-rendered _layouts (which bypass Laravel View
                // composers). Runs before every page render.
                $controller->bindEvent('page.init', function () use ($controller): void {
                    $payload = resolve(ThemePayloadResolver::class)->resolve();
                    $controller->vars['themeData'] = $payload['themeData'];
                    $controller->vars['themeBrandStyle'] = $payload['themeBrandStyle'];
                    $controller->vars['themeNeutralStyle'] = $payload['themeNeutralStyle'];
                });

                $controller->bindEvent('page.init', function ($page) {
                    if (! isset($page->security) || $page->security == 'all') {
                        return;
                    }

                    $isAuthenticated = Auth::check();
                    if ($page->security == 'customer' && ! $isAuthenticated) {
                        return redirect()->guest(page_url('home'));
                    }

                    if ($page->security == 'guest' && $isAuthenticated) {
                        return redirect()->guest(page_url('home'));
                    }
                });
            });
        }

        $translationNs = $this->translationNamespace();

        Event::listen('admin.form.extendFields', function (Form $widget) use ($translationNs): void {
            if (! isset($widget->data->fileSource)) {
                return;
            }

            if ($widget->data->fileSource instanceof Page) {
                $widget->addFields([
                    'settings[security]' => [
                        'tab' => 'igniter::system.themes.text_tab_meta',
                        'label' => "{$translationNs}::default.label_security",
                        'type' => 'checkboxtoggle',
                        'default' => 'all',
                        'span' => 'right',
                        'options' => [
                            'all' => "{$translationNs}::default.text_all",
                            'customer' => "{$translationNs}::default.text_customer",
                            'guest' => "{$translationNs}::default.text_guest",
                        ],
                        'comment' => "{$translationNs}::default.help_security",
                    ],
                ], 'primary');
            }
        });
    }

    /**
     * Configure Google Fonts to use the font URL saved in theme settings.
     */
    protected function configureGoogleFonts(): void
    {
        $this->callAfterResolving(GoogleFonts::class, function (GoogleFonts $googleFonts): void {
            $themeData = resolve(ThemeManager::class)->getActiveTheme()?->getCustomData() ?? [];
            if (data_get($themeData, 'font-download')) {
                config()->set('google-fonts.fonts.default', data_get($themeData, 'font-url'));
            }
        });
    }

    /**
     * Register persistent Livewire middleware and set the pagination theme.
     */
    protected function configureLivewire(): void
    {
        Livewire::addPersistentMiddleware([
            CheckLocation::class,
            CartMiddleware::class,
        ]);

        config()->set('livewire.pagination_theme', 'tailwind');
    }

    /**
     * Register the BannerManager form widget with TastyIgniter.
     */
    protected function registerFormWidgets(): void
    {
        resolve(Widgets::class)->registerFormWidgets(function (Widgets $manager): void {
            $manager->registerFormWidget(BannerManager::class, [
                'label' => 'Banner Manager',
                'code' => 'bannermanager',
            ]);
        });
    }

    /**
     * Define theme routes using the `routes()` array returned by the child
     * theme. Each entry is `[method, uri, action, name]`.
     */
    protected function defineRoutes(): void
    {
        $routeDefinitions = $this->routes();

        if (empty($routeDefinitions)) {
            return;
        }

        Route::middleware(config('igniter-routes.middleware', []))
            ->domain(config('igniter-routes.domain'))
            ->name($this->routePrefix())
            ->prefix(Igniter::uri())
            ->group(function ($router) use ($routeDefinitions): void {
                foreach ($routeDefinitions as [$method, $uri, $action, $name]) {
                    $router->{$method}($uri, $action)->name($name);
                }
            });
    }
}
