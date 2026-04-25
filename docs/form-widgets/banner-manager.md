# BannerManager Widget

A form widget for managing hero banner slides with images, titles, descriptions, and call-to-action buttons.

## Usage

The widget is automatically registered by `AbstractThemeServiceProvider` with the code `bannermanager`. Include it in your theme's `fields.php`:

```php
use TiPowerUp\ThemeToolkit\Fields\BaseSchema;

return [
    'form' => BaseSchema::merge(
        ['tabs' => BaseSchema::tabs()],
        // BaseSchema::tabs() already includes a 'banners' tab with this field
    )['tabs'],
];
```

Or if composing custom tabs:

```php
'banners' => [
    'title' => 'Hero Banners',
    'fields' => [
        'banners' => [
            'label' => 'Banner Slides',
            'type' => 'bannermanager',
            'commentAbove' => 'Add hero slider banners',
            'rules' => 'nullable|array',
        ],
    ],
],
```

## Saved Data Structure

When a banner row is saved, the widget stores an array of banner objects. Each row is shaped as:

```php
[
    'image' => '/path/to/image.jpg',         // Media library path
    'title' => 'Welcome to our site',        // Banner title
    'description' => 'We deliver amazing...', // Banner description
    'cta_text' => 'Order Now',               // Call-to-action button text
    'cta_link' => '/menu',                   // Call-to-action link (URL or page slug)
]
```

Empty rows (missing `image`) are filtered out during save.

## Rendering Banners in Views

Query the theme data and loop over the banners:

```blade
@php
    $banners = $themeData['banners'] ?? [];
@endphp

@if ($banners)
    <div class="banner-slider">
        @foreach ($banners as $banner)
            <div class="banner-slide"
                 style="background-image: url('{{ $banner['preview_url'] ?? asset('vendor/myvendor-myname/img/'.$banner['image']) }}')">
                <div class="banner-content">
                    <h2 class="text-3xl font-bold">{{ $banner['title'] }}</h2>
                    <p class="mt-2">{{ $banner['description'] }}</p>

                    @if ($banner['cta_text'] && $banner['cta_link'])
                        <a href="{{ $banner['cta_link'] }}"
                           class="btn btn-primary mt-4">
                            {{ $banner['cta_text'] }}
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif
```

## Admin Interface

In the theme customizer (**Design → Themes → Customize → Banners tab**):

1. Click **Add Row** to add a new banner.
2. Click the image slot to open the media manager and select an image.
3. Enter the banner title, description, and CTA button text/link.
4. Click **Save** to persist.

Rows without an image are considered incomplete and filtered out on save.

## Widget Internals

The widget:
- Extends `BaseFormWidget` from TastyIgniter.
- Resolves partials via the `tipowerup.theme-toolkit` view namespace (not the child theme's namespace), ensuring portability.
- Uses the shared `$.ti.mediaManager.modal` JS API to open the media picker without workarounds.
- Each row is validated and normalized to the standard shape.

Empty rows are automatically removed during `getSaveValue()` to keep the database clean.

## Related

- [BaseSchema reference](../fields-schema.md)
- [Theme customizer documentation](https://docs.tastyigniter.com/getting-started/themes)
