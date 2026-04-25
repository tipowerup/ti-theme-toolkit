<?php

declare(strict_types=1);

namespace TiPowerUp\ThemeToolkit\FormWidgets;

use Igniter\Admin\Classes\BaseFormWidget;
use Igniter\Main\Classes\MediaLibrary;
use Override;

/**
 * BannerManager — a custom form widget for managing hero banner slides.
 *
 * Works around TastyIgniter's "mediafinder inside repeater" layout bug by
 * rendering its own row UI and invoking the media manager modal directly via
 * the shared $.ti.mediaManager.modal JS API. Each row has: image, title,
 * description, CTA button text, CTA link.
 *
 * Partials are resolved via the `tipowerup.theme-toolkit` view namespace so
 * this widget does not depend on the consuming theme's FQCN-derived namespace.
 */
class BannerManager extends BaseFormWidget
{
    protected string $defaultAlias = 'bannermanager';

    #[Override]
    public function render(): string
    {
        $this->prepareVars();

        return $this->makePartial('tipowerup.theme-toolkit::_partials.formwidgets.bannermanager.bannermanager');
    }

    /**
     * Prepare template variables for rendering.
     */
    public function prepareVars(): void
    {
        $rows = $this->normalizeValue($this->getLoadValue());

        $mediaLibrary = resolve(MediaLibrary::class);
        foreach ($rows as &$row) {
            $row['preview_url'] = '';
            if (! empty($row['image'])) {
                try {
                    $row['preview_url'] = $mediaLibrary->getMediaUrl($row['image']);
                } catch (\Throwable) {
                    // Media path missing or library unavailable — leave blank.
                }
            }
        }
        unset($row);

        $this->vars['name'] = $this->formField->getName();
        $this->vars['value'] = $rows;
        $this->vars['alias'] = $this->alias;
        $this->vars['emptyRow'] = self::emptyRow();
    }

    #[Override]
    public function getSaveValue(mixed $value): mixed
    {
        $rows = $this->normalizeValue($value);

        return array_values(array_filter($rows, static fn (array $row): bool => ! empty($row['image'])));
    }

    /**
     * Shape of a blank banner row — single source of truth for both PHP and
     * the Blade "add row" template.
     *
     * @return array{image: string, title: string, description: string, cta_text: string, cta_link: string}
     */
    public static function emptyRow(): array
    {
        return [
            'image' => '',
            'title' => '',
            'description' => '',
            'cta_text' => '',
            'cta_link' => '',
        ];
    }

    /**
     * Ensure the loaded value is always an array of rows with the expected keys.
     *
     * @return array<int, array{image: string, title: string, description: string, cta_text: string, cta_link: string}>
     */
    protected function normalizeValue(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $rows = [];
        foreach ($value as $row) {
            if (! is_array($row)) {
                continue;
            }
            $rows[] = [
                'image' => (string) ($row['image'] ?? ''),
                'title' => (string) ($row['title'] ?? ''),
                'description' => (string) ($row['description'] ?? ''),
                'cta_text' => (string) ($row['cta_text'] ?? ''),
                'cta_link' => (string) ($row['cta_link'] ?? ''),
            ];
        }

        return $rows;
    }
}
