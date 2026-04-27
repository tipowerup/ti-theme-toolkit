<?php

declare(strict_types=1);

namespace TiPowerUp\ThemeToolkit\Support;

use Igniter\Main\Models\Theme;

/**
 * Resolves per-request theme payload: raw data array, derived brand-style CSS
 * string, and the primary color value. Results are cached for the lifetime of
 * the singleton (i.e. per Laravel request when registered via `singleton()`).
 *
 * TastyIgniter's Theme::getCustomData() uses array_get() on raw form-field
 * keys (e.g. 'color[primary]'), which treats them as literal dot-notation paths
 * — so `$theme->color` always returns null when fields use bracket notation.
 * This class reads the `data` column directly to avoid that issue.
 *
 * <code>
 * $resolver = app(ThemePayloadResolver::class);
 * ['themeData' => $data, 'themeBrandStyle' => $style, 'primary' => $primary] = $resolver->resolve();
 * </code>
 */
final class ThemePayloadResolver
{
    /**
     * Color keys allowed in the brand-style <html style=""> attribute.
     * Restricting to a whitelist prevents arbitrary field names from the DB
     * leaking into the rendered CSS-var list.
     */
    private const SECONDARY_COLOR_KEYS = [
        'secondary', 'secondary_light', 'secondary_dark',
        'success', 'danger', 'warning', 'info',
    ];

    private const NEUTRAL_COLOR_KEYS = ['text', 'text_muted', 'body', 'surface', 'border'];

    /** @var array<string, array{themeData: array<string, mixed>, themeBrandStyle: string, themeNeutralStyle: string, primary: ?string}> */
    private array $cache = [];

    /**
     * Resolve the theme payload for the currently active theme.
     *
     * @return array{themeData: array<string, mixed>, themeBrandStyle: string, themeNeutralStyle: string, primary: ?string}
     */
    public function resolve(): array
    {
        $theme = controller()?->getTheme();
        $code = $theme?->getName() ?? '__none__';

        if (! array_key_exists($code, $this->cache)) {
            $themeData = $theme
                ? (Theme::where('code', $code)->value('data') ?? [])
                : [];
            $primary = $themeData['color']['primary'] ?? null;

            $this->cache[$code] = [
                'themeData' => $themeData,
                'themeBrandStyle' => $this->buildBrandStyle($themeData),
                'themeNeutralStyle' => $this->buildNeutralStyle($themeData),
                'primary' => $primary,
            ];
        }

        return $this->cache[$code];
    }

    /**
     * Build the brand-color CSS-var string for the <html style=""> attribute.
     *
     * Rendering on <html> server-side prevents wire:navigate's DOM morph from
     * stripping admin colors: if the target HTML has no style attribute, morph
     * removes one set via JS, causing a flash of the default color.
     *
     * @param  array<string, mixed>  $themeData
     */
    public function buildBrandStyle(array $themeData): string
    {
        $colors = $themeData['color'] ?? [];
        $primary = $colors['primary'] ?? null;

        if (! $primary) {
            return '';
        }

        $vars = ColorHelper::derivePrimaryPalette($primary);

        foreach (self::SECONDARY_COLOR_KEYS as $key) {
            if (! empty($colors[$key])) {
                $vars['--color-'.str_replace('_', '-', $key)] = ColorHelper::hexToRgb($colors[$key]);
            }
        }

        $out = '';
        foreach ($vars as $k => $v) {
            // Defensive: only emit values that match the expected
            // `rgb(r g b)` shape ColorHelper produces. Anything else would
            // be a bug upstream, but guarding here closes the attribute-
            // escape surface regardless.
            if (! preg_match('/^rgb\(\d{1,3} \d{1,3} \d{1,3}\)$/', $v)) {
                continue;
            }
            $out .= $k.':'.$v.';';
        }

        return $out;
    }

    /**
     * Build the neutral-color override `<style>` block for light mode.
     *
     * Neutrals can't ride on the <html style=""> attribute because they need
     * to be scoped to `:root:not(.dark)` so dark-mode overrides in the
     * compiled theme stylesheet still win. Inline styles can't carry that
     * condition.
     *
     * @param  array<string, mixed>  $themeData
     */
    public function buildNeutralStyle(array $themeData): string
    {
        $colors = $themeData['color'] ?? [];

        $vars = '';
        foreach (self::NEUTRAL_COLOR_KEYS as $key) {
            if (empty($colors[$key])) {
                continue;
            }

            $value = ColorHelper::hexToRgb($colors[$key]);
            if (! preg_match('/^rgb\(\d{1,3} \d{1,3} \d{1,3}\)$/', $value)) {
                continue;
            }

            $vars .= '--color-'.str_replace('_', '-', $key).':'.$value.';';
        }

        return $vars === '' ? '' : '<style>:root:not(.dark){'.$vars.'}</style>';
    }

    /**
     * Flush the internal cache. Useful in tests where the container is reset
     * between test cases but the singleton instance persists.
     */
    public function flush(): void
    {
        $this->cache = [];
    }
}
