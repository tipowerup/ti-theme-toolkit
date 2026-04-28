<?php

declare(strict_types=1);

use TiPowerUp\ThemeToolkit\Tests\Fixtures\StubThemeServiceProvider;

beforeEach(function (): void {
    $this->sp = new StubThemeServiceProvider($this->app);
});

it('substitutes {ns} with the active theme view namespace', function (): void {
    $resolved = $this->sp->publicResolveComponentMeta([
        'code' => '{ns}::login',
    ]);

    expect($resolved['code'])->toBe('tipowerup-stub::login');
});

it('substitutes {lang} with the translation namespace (vendor.rest convention)', function (): void {
    // 'tipowerup-stub' → vendor=tipowerup, rest=stub → 'tipowerup.stub'
    $resolved = $this->sp->publicResolveComponentMeta([
        'name' => '{lang}::default.component_login_title',
    ]);

    expect($resolved['name'])->toBe('tipowerup.stub::default.component_login_title');
});

it('substitutes both placeholders in the same string and across keys', function (): void {
    $resolved = $this->sp->publicResolveComponentMeta([
        'code' => '{ns}::login',
        'name' => '{lang}::default.title',
        'description' => '{ns} component using {lang} locale',
    ]);

    expect($resolved)->toMatchArray([
        'code' => 'tipowerup-stub::login',
        'name' => 'tipowerup.stub::default.title',
        'description' => 'tipowerup-stub component using tipowerup.stub locale',
    ]);
});

it('leaves strings without placeholders untouched', function (): void {
    $resolved = $this->sp->publicResolveComponentMeta([
        'code' => 'literal-code',
        'name' => 'Plain Title',
    ]);

    expect($resolved)->toMatchArray([
        'code' => 'literal-code',
        'name' => 'Plain Title',
    ]);
});

it('leaves non-string keys untouched (e.g. arrays in extra meta)', function (): void {
    $resolved = $this->sp->publicResolveComponentMeta([
        'code' => '{ns}::x',
        'tags' => ['a', 'b'],
    ]);

    expect($resolved['tags'])->toBe(['a', 'b']);
});
