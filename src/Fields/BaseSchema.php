<?php

declare(strict_types=1);

namespace TiPowerUp\ThemeToolkit\Fields;

/**
 * BaseSchema provides the canonical 7-tab field structure shared across all
 * TiPowerUp themes. Child themes compose their `fields.php` by calling
 * `BaseSchema::tabs()` and then selectively overriding or extending via
 * `BaseSchema::merge()`.
 *
 * Theme-specific defaults (e.g. a specific primary color) are NOT set here;
 * they should be added by the consuming theme's merge override so that the
 * base schema remains neutral and reusable.
 *
 * <code>
 * // resources/meta/fields.php in a child theme:
 * use TiPowerUp\ThemeToolkit\Fields\BaseSchema;
 *
 * return [
 *     'form' => BaseSchema::merge(
 *         ['tabs' => BaseSchema::tabs()],
 *         ['tabs' => [
 *             'colors' => [
 *                 'fields' => [
 *                     'color[primary]' => ['default' => '#f97316'],
 *                 ],
 *             ],
 *         ]]
 *     )['tabs'],
 * ];
 * </code>
 */
final class BaseSchema
{
    /**
     * Return the full 7-tab schema structure, keyed by tab identifier.
     *
     * Tab keys: general, banners, colors, dark_mode, social, advanced, gdpr.
     *
     * Color fields intentionally omit `default` values — child themes set
     * their own brand defaults via `merge()`.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function tabs(): array
    {
        return [
            'general' => [
                'title' => 'General',
                'fields' => [
                    'logo_image' => [
                        'label' => 'Logo Image',
                        'type' => 'mediafinder',
                        'span' => 'left',
                        'comment' => 'Upload a logo image for your theme',
                        'rules' => 'nullable|string',
                    ],
                    'logo_text' => [
                        'label' => 'Logo Text',
                        'type' => 'text',
                        'span' => 'right',
                        'comment' => 'Alternatively, enter text to display as logo',
                        'rules' => 'nullable|string',
                    ],
                    'favicon' => [
                        'label' => 'Favicon',
                        'type' => 'mediafinder',
                        'span' => 'left',
                        'comment' => 'Upload a favicon for your theme (recommended: 32x32 or 16x16 .ico file)',
                        'rules' => 'nullable|string',
                    ],
                    'font[url]' => [
                        'label' => 'Google Font URL',
                        'type' => 'text',
                        'span' => 'right',
                        'default' => 'https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap',
                        'comment' => 'Grab your CSS URL from <a href="https://fonts.google.com/" target="_blank">Google Fonts</a> and paste it here.',
                        'rules' => 'required|startsWith:https://fonts.googleapis.com/',
                    ],
                ],
            ],
            'banners' => [
                'title' => 'Banners',
                'fields' => [
                    'banners' => [
                        'label' => 'Hero Banner Slides',
                        'type' => 'bannermanager',
                        'commentAbove' => 'Hero slider banners. Click the image slot to pick from the media library.',
                        'rules' => 'nullable|array',
                    ],
                ],
            ],
            'colors' => [
                'title' => 'Colors',
                'fields' => [
                    'color[primary]' => [
                        'label' => 'Primary Color',
                        'type' => 'colorpicker',
                        'span' => 'left',
                        'rules' => 'required',
                        'comment' => 'Main brand color — light, dark and hover shades are derived automatically',
                    ],
                    'color[secondary]' => [
                        'label' => 'Secondary Color',
                        'type' => 'colorpicker',
                        'span' => 'right',
                        'rules' => 'required',
                    ],
                    'color[success]' => [
                        'label' => 'Success Color',
                        'type' => 'colorpicker',
                        'span' => 'left',
                        'rules' => 'required',
                    ],
                    'color[danger]' => [
                        'label' => 'Danger Color',
                        'type' => 'colorpicker',
                        'span' => 'right',
                        'rules' => 'required',
                    ],
                    'color[warning]' => [
                        'label' => 'Warning Color',
                        'type' => 'colorpicker',
                        'span' => 'left',
                        'rules' => 'required',
                    ],
                    'color[info]' => [
                        'label' => 'Info Color',
                        'type' => 'colorpicker',
                        'span' => 'right',
                        'rules' => 'required',
                    ],
                    'color[text]' => [
                        'label' => 'Text Color',
                        'type' => 'colorpicker',
                        'span' => 'left',
                        'rules' => 'required',
                        'comment' => 'Main text color',
                    ],
                    'color[text_muted]' => [
                        'label' => 'Text Muted',
                        'type' => 'colorpicker',
                        'span' => 'right',
                        'rules' => 'required',
                        'comment' => 'Secondary text color',
                    ],
                    'color[body]' => [
                        'label' => 'Body Background',
                        'type' => 'colorpicker',
                        'span' => 'left',
                        'rules' => 'required',
                    ],
                    'color[surface]' => [
                        'label' => 'Surface Background',
                        'type' => 'colorpicker',
                        'span' => 'right',
                        'rules' => 'required',
                        'comment' => 'Cards, panels background',
                    ],
                    'color[border]' => [
                        'label' => 'Border Color',
                        'type' => 'colorpicker',
                        'span' => 'left',
                        'rules' => 'required',
                    ],
                ],
            ],
            'dark_mode' => [
                'title' => 'Dark Mode',
                'fields' => [
                    'dark_mode[enabled]' => [
                        'label' => 'Enable Dark Mode',
                        'type' => 'switch',
                        'default' => true,
                        'rules' => 'boolean',
                        'comment' => 'Allow users to toggle dark mode',
                    ],
                    'dark_mode[default]' => [
                        'label' => 'Default Mode',
                        'type' => 'select',
                        'default' => 'system',
                        'options' => [
                            'system' => 'System Preference',
                            'light' => 'Light',
                            'dark' => 'Dark',
                        ],
                        'rules' => 'required|in:system,light,dark',
                    ],
                ],
            ],
            'social' => [
                'title' => 'Social Links',
                'fields' => [
                    'social[facebook]' => [
                        'label' => 'Facebook URL',
                        'type' => 'text',
                        'span' => 'left',
                        'rules' => 'nullable|url',
                    ],
                    'social[twitter]' => [
                        'label' => 'Twitter URL',
                        'type' => 'text',
                        'span' => 'right',
                        'rules' => 'nullable|url',
                    ],
                    'social[instagram]' => [
                        'label' => 'Instagram URL',
                        'type' => 'text',
                        'span' => 'left',
                        'rules' => 'nullable|url',
                    ],
                    'social[youtube]' => [
                        'label' => 'YouTube URL',
                        'type' => 'text',
                        'span' => 'right',
                        'rules' => 'nullable|url',
                    ],
                ],
            ],
            'advanced' => [
                'title' => 'Advanced',
                'fields' => [
                    'ga_tracking_code' => [
                        'label' => 'Google Analytics Tracking Code',
                        'type' => 'codeeditor',
                        'size' => 'small',
                        'mode' => 'js',
                        'comment' => 'Paste your Google Analytics Tracking Code here.',
                        'rules' => 'nullable|string',
                    ],
                    'custom_css' => [
                        'label' => 'Custom CSS',
                        'type' => 'codeeditor',
                        'mode' => 'css',
                        'span' => 'left',
                        'size' => 'small',
                        'comment' => 'Add custom CSS styles',
                        'rules' => 'nullable|string',
                    ],
                    'custom_js' => [
                        'label' => 'Custom JavaScript',
                        'type' => 'codeeditor',
                        'mode' => 'javascript',
                        'span' => 'right',
                        'size' => 'small',
                        'comment' => 'Add custom JavaScript code',
                        'rules' => 'nullable|string',
                    ],
                ],
            ],
            'gdpr' => [
                'title' => 'GDPR (EU Cookie Settings)',
                'fields' => [
                    'gdpr[enabled]' => [
                        'label' => 'Enable Cookie Banner',
                        'type' => 'switch',
                        'default' => true,
                        'rules' => 'boolean',
                    ],
                    'gdpr[message]' => [
                        'label' => 'Cookie Message',
                        'type' => 'textarea',
                        'default' => 'We use cookies to improve our services. If you continue to browse, consider accepting its use.',
                        'rules' => 'required|string',
                        'attributes' => [
                            'rows' => '4',
                        ],
                    ],
                    'gdpr[accept_text]' => [
                        'label' => 'Accept Button Text',
                        'type' => 'text',
                        'default' => 'Accept',
                        'rules' => 'required|max:128',
                    ],
                    'gdpr[more_info_text]' => [
                        'label' => 'More Info Text',
                        'type' => 'text',
                        'default' => 'More Information',
                        'rules' => 'required|max:128',
                    ],
                    'gdpr[more_info_link]' => [
                        'label' => 'More Info Link',
                        'type' => 'select',
                        // Guarded so themes without the igniter-pages extension
                        // can still use BaseSchema without a hard dependency.
                        'options' => class_exists(\Igniter\Pages\Models\Page::class)
                            ? \Igniter\Pages\Models\Page::getDropdownOptions(...)
                            : [],
                        'rules' => 'nullable|string',
                    ],
                ],
            ],
        ];
    }

    /**
     * Recursively merge an overrides array into the base schema array.
     *
     * This uses array_replace_recursive semantics with one important caveat:
     * numeric-keyed (sequential) arrays are REPLACED entirely rather than
     * merged, because merging numeric arrays produces duplicates in option
     * lists. String-keyed arrays at any depth are merged key-by-key.
     *
     * Typical use: merging per-theme defaults into specific field definitions.
     *
     * <code>
     * $schema = BaseSchema::merge(
     *     ['tabs' => BaseSchema::tabs()],
     *     ['tabs' => [
     *         'colors' => [
     *             'fields' => [
     *                 'color[primary]' => ['default' => '#f97316'],
     *             ],
     *         ],
     *     ]]
     * );
     * </code>
     *
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function merge(array $base, array $overrides): array
    {
        // A numeric-keyed (list-like) override replaces the base entry wholesale
        // to avoid producing duplicated option lists.
        if (self::isList($overrides)) {
            return $overrides;
        }

        foreach ($overrides as $key => $value) {
            if (isset($base[$key]) && is_array($base[$key]) && is_array($value)) {
                $base[$key] = self::merge($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    /**
     * Detect a sequential (zero-indexed, numeric-keyed) array. Empty arrays
     * are treated as lists so an explicit `[]` override clears the base.
     *
     * @param  array<array-key, mixed>  $value
     */
    private static function isList(array $value): bool
    {
        return $value === [] || array_is_list($value);
    }
}
