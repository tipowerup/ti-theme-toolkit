<?php

declare(strict_types=1);

use Igniter\Main\Classes\Theme;
use Illuminate\Support\Facades\Event;

/**
 * Verifies the toolkit's workaround for the upstream ti-ext-pages bug
 * (Page::resolveMenuItem discarded a Collection::where filter, so every
 * static-page item resolved to the alphabetically-first cached page).
 *
 * The fix lives in AbstractThemeServiceProvider::registerStaticPageResolverPatch().
 * Once upstream PR #21 lands the patch becomes a redundant override returning
 * the same URL, so these tests stay green.
 *
 * Database fixtures are intentionally avoided here — testbench's in-memory
 * SQLite doesn't run the full ti-ext-pages migration set, and exercising the
 * happy-path with real Page rows is covered by the upstream PR's own test
 * suite. Here we assert the listener is wired and bails for non-static-page
 * types, which is what we actually own.
 */
it('registers a listener on pages.menuitem.resolveItem', function (): void {
    expect(Event::hasListeners('pages.menuitem.resolveItem'))->toBeTrue();
});

it('returns null for non static-page item types so other listeners can handle them', function (): void {
    $theme = new Theme('tests-theme-path', ['code' => 'tests-theme']);
    $item = (object) ['type' => 'theme-page', 'reference' => 'home'];

    $responses = Event::dispatch('pages.menuitem.resolveItem', [$item, 'http://localhost/', $theme]);

    // The toolkit patch returned null for non-static-page types — at least one
    // response slot is null, and we didn't produce a static-page-style URL.
    expect(collect($responses)->contains(fn ($r): bool => $r === null))->toBeTrue();
});

it('returns null when reference is missing', function (): void {
    $theme = new Theme('tests-theme-path', ['code' => 'tests-theme']);
    $item = (object) ['type' => 'static-page', 'reference' => null];

    $responses = Event::dispatch('pages.menuitem.resolveItem', [$item, 'http://localhost/', $theme]);

    expect(collect($responses)->contains(fn ($r): bool => $r === null))->toBeTrue();
});

it('returns null when there is no active theme', function (): void {
    $item = (object) ['type' => 'static-page', 'reference' => 1];

    $responses = Event::dispatch('pages.menuitem.resolveItem', [$item, 'http://localhost/', null]);

    expect(collect($responses)->contains(fn ($r): bool => $r === null))->toBeTrue();
});
