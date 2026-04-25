# BaseSchema and Field Composition

`BaseSchema` provides a canonical 7-tab field structure shared across all TiPowerUp themes. Themes compose their `fields.php` by merging the base schema with theme-specific overrides.

## BaseSchema::tabs() : array

Returns the full 7-tab structure:

| Tab Key | Title | Fields |
|---------|-------|--------|
| `general` | General | logo_image, logo_text, favicon, font[url] |
| `banners` | Banners | banners (BannerManager widget) |
| `colors` | Colors | color[primary], color[secondary], color[success], color[danger], color[warning], color[info], color[text], color[text_muted], color[body], color[surface], color[border] |
| `dark_mode` | Dark Mode | dark_mode[enabled], dark_mode[default] |
| `social` | Social Links | social[facebook], social[twitter], social[instagram], social[youtube] |
| `advanced` | Advanced | ga_tracking_code, custom_css, custom_js |
| `gdpr` | GDPR (EU Cookie Settings) | gdpr[enabled], gdpr[message], gdpr[accept_text], gdpr[more_info_text], gdpr[more_info_link] |

Color fields intentionally omit default values — child themes set their own brand defaults via `merge()`.

### Example Usage

```php
use TiPowerUp\ThemeToolkit\Fields\BaseSchema;

$tabs = BaseSchema::tabs();

// Access the colors tab
$colorFields = $tabs['colors']['fields'];

// All color fields: color[primary], color[secondary], etc.
foreach ($colorFields as $fieldName => $fieldConfig) {
    // $fieldName = 'color[primary]'
    // $fieldConfig = ['label' => '...', 'type' => 'colorpicker', ...]
}
```

## BaseSchema::merge(array $base, array $overrides) : array

Recursively merges an overrides array into the base schema. Uses string-key merging (arrays with numeric keys are replaced entirely to avoid duplicates in option lists).

### Signature

```php
public static function merge(array $base, array $overrides): array
```

### Behavior

- **String-keyed (associative) arrays**: Merged recursively, key-by-key.
- **List-like arrays** (sequential numeric keys, detected via `array_is_list()`): Replaced wholesale. An explicit `[]` override clears the base entry.
- Non-array values in overrides always replace base values.

### Example 1: Set Theme Defaults

```php
use TiPowerUp\ThemeToolkit\Fields\BaseSchema;

return [
    'form' => BaseSchema::merge(
        ['tabs' => BaseSchema::tabs()],
        ['tabs' => [
            'colors' => [
                'fields' => [
                    'color[primary]' => ['default' => '#f97316'],   // Orange
                    'color[secondary]' => ['default' => '#6b7280'], // Gray
                ],
            ],
        ]],
    )['tabs'],
];
```

### Example 2: Add a Custom Tab

```php
return [
    'form' => BaseSchema::merge(
        ['tabs' => BaseSchema::tabs()],
        ['tabs' => [
            'my_custom_tab' => [
                'title' => 'My Custom Settings',
                'fields' => [
                    'custom_setting' => [
                        'label' => 'My Setting',
                        'type' => 'text',
                    ],
                ],
            ],
        ]],
    )['tabs'],
];
```

### Example 3: Extend an Existing Tab

```php
return [
    'form' => BaseSchema::merge(
        ['tabs' => BaseSchema::tabs()],
        ['tabs' => [
            'general' => [
                'fields' => [
                    'my_new_field' => [
                        'label' => 'New Field',
                        'type' => 'text',
                        'span' => 'left',
                    ],
                ],
            ],
        ]],
    )['tabs'],
];
```

This adds `my_new_field` to the `general` tab while keeping the existing logo, favicon, and font fields.

### Example 4: Override Field Properties

```php
return [
    'form' => BaseSchema::merge(
        ['tabs' => BaseSchema::tabs()],
        ['tabs' => [
            'dark_mode' => [
                'fields' => [
                    'dark_mode[enabled]' => [
                        'default' => false,  // Change from true
                        'comment' => 'Custom help text',
                    ],
                ],
            ],
        ]],
    )['tabs'],
];
```

## Field Reference

### General Tab

| Field | Type | Notes |
|-------|------|-------|
| `logo_image` | mediafinder | Optional logo image |
| `logo_text` | text | Fallback to text if no image |
| `favicon` | mediafinder | 32x32 or 16x16 ICO recommended |
| `font[url]` | text | Google Fonts CSS URL, validated for HTTPS |

### Banners Tab

| Field | Type | Notes |
|-------|------|-------|
| `banners` | bannermanager | Hero slider rows (image, title, description, cta_text, cta_link) |

### Colors Tab

All color pickers default to `required` and `rules: 'required'`.

| Field | Notes |
|-------|-------|
| `color[primary]` | Base brand color; light, dark, and hover shades derived automatically |
| `color[secondary]` | Complementary brand color |
| `color[success]` | Green for success states |
| `color[danger]` | Red for error/danger states |
| `color[warning]` | Amber for warnings |
| `color[info]` | Blue for informational states |
| `color[text]` | Main body text color |
| `color[text_muted]` | Secondary/muted text |
| `color[body]` | Page background |
| `color[surface]` | Card/panel background |
| `color[border]` | Border/divider color |

Primary color automatically derives:
- `--color-primary-50` (95% tint toward white)
- `--color-primary-100` (88% tint)
- `--color-primary-400` (20% tint)
- `--color-primary-light` (25% tint)
- `--color-primary-dark` (15% shade)
- `--color-primary-900` (70% shade)

### Dark Mode Tab

| Field | Type | Default |
|-------|------|---------|
| `dark_mode[enabled]` | switch | `true` |
| `dark_mode[default]` | select | `'system'` (options: system, light, dark) |

### Social Links Tab

| Field | Type |
|-------|------|
| `social[facebook]` | text (URL) |
| `social[twitter]` | text (URL) |
| `social[instagram]` | text (URL) |
| `social[youtube]` | text (URL) |

All nullable.

### Advanced Tab

| Field | Type | Notes |
|-------|------|-------|
| `ga_tracking_code` | codeeditor (js) | Google Analytics code snippet |
| `custom_css` | codeeditor (css) | Custom CSS for the theme |
| `custom_js` | codeeditor (js) | Custom JavaScript |

All nullable.

### GDPR Tab

| Field | Type | Default |
|-------|------|---------|
| `gdpr[enabled]` | switch | `true` |
| `gdpr[message]` | textarea | "We use cookies to improve..." |
| `gdpr[accept_text]` | text | "Accept" |
| `gdpr[more_info_text]` | text | "More Information" |
| `gdpr[more_info_link]` | select | Null (optional link to privacy page) |

> **Note:** `gdpr[more_info_link]` populates its options from `Igniter\Pages\Models\Page::getDropdownOptions()`, guarded with `class_exists()`. Themes running without the `tastyigniter-ext-pages` extension get an empty option list rather than a fatal error — no action needed.

## Accessing Merged Fields

After merging:

```php
use TiPowerUp\ThemeToolkit\Fields\BaseSchema;

$merged = BaseSchema::merge(
    ['tabs' => BaseSchema::tabs()],
    ['tabs' => ['colors' => ['fields' => ['color[primary]' => ['default' => '#f97316']]]]]
);

$primaryColorField = $merged['tabs']['colors']['fields']['color[primary]'];
// ['label' => 'Primary Color', 'type' => 'colorpicker', 'default' => '#f97316', ...]
```

## Notes

- Always wrap the merged result in `['tabs' => ...]` to match the `fields.php` structure.
- The merge is **not** order-preserving for tabs. If tab order matters in the admin UI, override `viewNamespace()` or manage the `fields.php` structure manually.
- Color fields intentionally use bracket notation (`color[primary]`) to leverage TastyIgniter's bracket-key form field handling.
- The toolkit's `ThemePayloadResolver` reads bracket-key fields directly from the `themes.data` column to avoid the `array_get()` dot-notation issue.

## Related

- [Service Provider reference](../service-provider.md)
- [Color system and palette derivation](../color-system.md)
- [Getting started guide](../getting-started.md)
