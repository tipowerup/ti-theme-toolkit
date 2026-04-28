<?php

declare(strict_types=1);

use Livewire\LivewireManager;
use TiPowerUp\ThemeToolkit\Livewire\Login;
use TiPowerUp\ThemeToolkit\Livewire\NewsletterSubscribeForm;

/**
 * Smoke tests for AbstractThemeServiceProvider::boot() — verify the SP
 * registers shared toolkit infrastructure (view-namespace binding, Livewire
 * components scoped to the active theme) when a concrete child SP is booted.
 */
it('binds the active theme view namespace into the container', function (): void {
    expect($this->app->bound('tipowerup.theme.viewNamespace'))->toBeTrue()
        ->and($this->app['tipowerup.theme.viewNamespace'])->toBe('tipowerup-stub');
});

it('registers the toolkit Login component under the active theme namespace', function (): void {
    /** @var LivewireManager $manager */
    $manager = app(LivewireManager::class);

    $component = $manager->new('tipowerup-stub::login');

    expect(get_class($component))->toBe(Login::class);
});

it('registers the toolkit NewsletterSubscribeForm despite ending in "Form"', function (): void {
    /** @var LivewireManager $manager */
    $manager = app(LivewireManager::class);

    // Regression for the auto-loader's old `notName('*Form.php')` filter,
    // which incorrectly excluded real components whose name ended in "Form".
    $component = $manager->new('tipowerup-stub::newsletter-subscribe-form');

    expect(get_class($component))->toBe(NewsletterSubscribeForm::class);
});
