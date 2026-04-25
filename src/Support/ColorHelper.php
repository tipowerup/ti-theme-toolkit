<?php

declare(strict_types=1);

namespace TiPowerUp\ThemeToolkit\Support;

final class ColorHelper
{
    /**
     * Shade ratios applied to the primary color to derive its palette.
     * Positive = shade toward black; negative = tint toward white.
     *
     * @var array<string, float>
     */
    public const PRIMARY_SHADE_MAP = [
        '--color-primary-50' => -0.95,
        '--color-primary-100' => -0.88,
        '--color-primary-400' => -0.20,
        '--color-primary-900' => 0.70,
        '--color-primary-light' => -0.25,
        '--color-primary-dark' => 0.15,
    ];

    /**
     * Convert #rgb / #rrggbb hex to "r g b" (space-separated, for `rgb(var(...) / <alpha>)`).
     */
    public static function hexToRgb(string $hex): string
    {
        [$r, $g, $b] = self::parseHex($hex);

        return "$r $g $b";
    }

    /**
     * Mix a hex color toward white. $amount 0..1 (higher = closer to white).
     */
    public static function tint(string $hex, float $amount): string
    {
        [$r, $g, $b] = self::parseHex($hex);
        $r = (int) round($r + (255 - $r) * $amount);
        $g = (int) round($g + (255 - $g) * $amount);
        $b = (int) round($b + (255 - $b) * $amount);

        return "$r $g $b";
    }

    /**
     * Mix a hex color toward black. $amount 0..1 (higher = closer to black).
     */
    public static function shade(string $hex, float $amount): string
    {
        [$r, $g, $b] = self::parseHex($hex);
        $r = (int) round($r * (1 - $amount));
        $g = (int) round($g * (1 - $amount));
        $b = (int) round($b * (1 - $amount));

        return "$r $g $b";
    }

    /**
     * Apply PRIMARY_SHADE_MAP ratios to derive the full primary palette.
     *
     * @return array<string, string> map of CSS var => "r g b"
     */
    public static function derivePrimaryPalette(string $hex): array
    {
        $out = ['--color-primary' => self::hexToRgb($hex)];
        foreach (self::PRIMARY_SHADE_MAP as $var => $ratio) {
            $out[$var] = $ratio >= 0
                ? self::shade($hex, $ratio)
                : self::tint($hex, -$ratio);
        }

        return $out;
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function parseHex(string $hex): array
    {
        if (! preg_match('/^#?([0-9a-f]{3}|[0-9a-f]{6})$/i', $hex)) {
            return [0, 0, 0];
        }

        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }
}
