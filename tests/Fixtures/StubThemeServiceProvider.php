<?php

declare(strict_types=1);

namespace TiPowerUp\ThemeToolkit\Tests\Fixtures;

use TiPowerUp\ThemeToolkit\AbstractThemeServiceProvider;

/**
 * Concrete subclass used as a fixture for tests that need a booted toolkit
 * service provider but don't want a full theme on disk. `themeCode()` returns
 * a stable string so view/translation namespaces are deterministic; paths
 * resolve under this fixtures directory so loadViewsFrom / loadTranslationsFrom
 * don't choke on missing dirs.
 */
class StubThemeServiceProvider extends AbstractThemeServiceProvider
{
    public const STUB_CODE = 'tipowerup-stub';

    protected function themeCode(): string
    {
        return self::STUB_CODE;
    }

    protected function packageRoot(): string
    {
        return __DIR__.'/StubTheme';
    }

    /**
     * Expose the protected meta resolver for direct unit testing.
     *
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    public function publicResolveComponentMeta(array $meta): array
    {
        return $this->resolveComponentMeta($meta);
    }
}
