<?php

declare(strict_types=1);

namespace TiPowerUp\ThemeToolkit\Tests;

use Illuminate\Foundation\Application;
use Tipowerup\Testbench\TestCase as BaseTestCase;
use TiPowerUp\ThemeToolkit\Tests\Fixtures\StubThemeServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getExtensionBasePath(): string
    {
        return dirname(__DIR__);
    }

    /**
     * @return array<int, class-string>
     */
    protected function getExtensionProviders(): array
    {
        return [StubThemeServiceProvider::class];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }
}
