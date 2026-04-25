<?php

declare(strict_types=1);

namespace TiPowerUp\ThemeToolkit\Data;

use Igniter\Cart\Models\Mealtime;
use Igniter\Cart\Models\Menu;
use Igniter\Local\Facades\Location;
use Illuminate\Support\Collection;

class MenuItemData
{
    public int $id;

    public string $name;

    public string $description;

    protected ?float $price = null;

    public ?float $priceBeforeSpecial = null;

    public ?int $minimumQuantity = 0;

    protected ?bool $mealtimeIsAvailable = null;

    /**
     * Create a MenuItemData instance wrapping a Menu model.
     */
    public function __construct(public Menu $model)
    {
        $this->id = $model->getBuyableIdentifier();
        $this->name = $model->getBuyableName();
        $this->description = nl2br(e($model->menu_description ?? ''));
        $this->priceBeforeSpecial = $model->menu_price;
        $this->minimumQuantity = $model->minimum_qty;
    }

    /**
     * Get the current selling price of the menu item (accounts for special pricing).
     */
    public function price(): float
    {
        if (! is_null($this->price)) {
            return $this->price;
        }

        return $this->price = $this->model->getBuyablePrice();
    }

    /**
     * Check if the menu item has ingredients.
     */
    public function hasIngredients(): bool
    {
        return $this->ingredients()->isNotEmpty();
    }

    /**
     * Get all active ingredients for the menu item.
     */
    public function ingredients(): Collection
    {
        return $this->model->ingredients->where('status', 1);
    }

    /**
     * Check if the menu item is available for the current order time/mealtime.
     */
    public function mealtimeIsAvailable(): bool
    {
        if (! is_null($this->mealtimeIsAvailable)) {
            return $this->mealtimeIsAvailable;
        }

        return $this->mealtimeIsAvailable = $this->model->isAvailable(Location::orderDateTime());
    }

    /**
     * Check if the menu item has customizable options.
     */
    public function hasOptions(): bool
    {
        return $this->model->hasOptions();
    }

    /**
     * Get all menu options sorted by priority.
     */
    public function getOptions(): Collection
    {
        return $this->model->menu_options->sortBy('priority');
    }

    /**
     * Check if the menu item has a thumbnail image.
     */
    public function hasThumb(): bool
    {
        return $this->model->hasMedia('thumb');
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function getThumb(array $options = [], ?string $tag = null): string
    {
        return $this->model->getThumbOrBlank($options, $tag);
    }

    /**
     * Check if a special offer is currently active for this menu item.
     */
    public function specialIsActive(): bool
    {
        return $this->model->special?->active() ?? false;
    }

    /**
     * Get the number of days remaining for an active special offer.
     *
     * @return int|null Days remaining, or null if no special
     */
    public function specialDaysRemaining(): ?int
    {
        return $this->model->special?->daysRemaining();
    }

    /**
     * Get a comma-separated list of mealtime titles when this menu item is available.
     */
    public function mealtimeTitles(): string
    {
        return $this->model->mealtimes
            ->filter(fn (Mealtime $mealtime): bool => $mealtime->isEnabled())
            ->pluck('description')
            ->join(', ');
    }

    /**
     * Get the menu item's detail page URL for the current or specified location.
     *
     * @param  string|null  $pageId  The page identifier (defaults to 'local.menus')
     */
    public function getUrl(?string $pageId = null): string
    {
        $current = Location::current();
        $slug = $this->model->locations->first()?->permalink_slug;
        if ($current && ($this->model->locations->isEmpty() || $this->model->locations->firstWhere('location_id', $current->getKey()))) {
            $slug = $current->permalink_slug;
        }

        $url = page_url($pageId ?? 'local.menus', ['location' => $slug]);

        return $url.'?menuId='.$this->model->getBuyableIdentifier();
    }
}
