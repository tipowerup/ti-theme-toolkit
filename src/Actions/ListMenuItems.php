<?php

declare(strict_types=1);

namespace TiPowerUp\ThemeToolkit\Actions;

use Igniter\Cart\Models\Menu as MenuModel;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;
use TiPowerUp\ThemeToolkit\Data\MenuItemData;

class ListMenuItems
{
    protected bool $hideUnavailable = false;

    protected Collection|Paginator $menuList;

    /** @var array<int|string, mixed> */
    protected array $menuListCategories = [];

    /**
     * Exclude unavailable (mealtime-restricted) items from the result.
     */
    public function hideUnavailable(bool $hideUnavailable = true): self
    {
        $this->hideUnavailable = $hideUnavailable;

        return $this;
    }

    /**
     * Execute the query with optional filters and eager-loaded relations.
     *
     * @param  array<string, mixed>  $filters
     * @param  string[]  $with  additional relations to eager-load
     */
    public function handle(array $filters, array $with = []): self
    {
        $with = array_merge([
            'mealtimes', 'media',
            'categories', 'categories.media', 'special', 'ingredients',
        ], $with);

        $menuList = MenuModel::query()
            ->withCount([
                'menu_options',
            ])
            ->with($with)
            ->listFrontEnd(array_except($filters, ['pageLimit']));

        if (! array_key_exists('pageLimit', $filters)) {
            $menuList = $this->processMenuItems($menuList->get());
        } else {
            $menuList = $menuList->simplePaginate($filters['pageLimit'], page: $filters['page'] ?? 1);
            $menuList->setCollection($this->processMenuItems($menuList->getCollection()));
        }

        if (! strlen((string) array_get($filters, 'category')) && array_get($filters, 'isGrouped', false)) {
            if (! array_key_exists('pageLimit', $filters)) {
                $menuList = $this->groupListByCategory($menuList);
            } else {
                $menuList->setCollection($this->groupListByCategory($menuList->getCollection()));
            }
        }

        $this->menuList = $menuList;

        return $this;
    }

    /**
     * Get the processed menu list (collection or paginator).
     */
    public function getList(): Collection|Paginator
    {
        return $this->menuList;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getCategoryList(): array
    {
        return $this->menuListCategories;
    }

    protected function groupListByCategory(mixed $items): Collection
    {
        $this->menuListCategories = [];

        $groupedList = [];
        foreach ($items as $menuItemObject) {
            $categories = $menuItemObject->model->categories;
            if (! $categories || $categories->isEmpty()) {
                $groupedList[0][] = $menuItemObject;

                continue;
            }

            foreach ($categories as $category) {
                $this->menuListCategories[$category->getKey()] = $category;
                $groupedList[$category->getKey()][] = $menuItemObject;
            }
        }

        return collect($groupedList)
            ->sortBy(function ($menuItems, $categoryId) {
                if (isset($this->menuListCategories[$categoryId])) {
                    return $this->menuListCategories[$categoryId]->priority;
                }

                return $categoryId;
            });
    }

    protected function processMenuItems(Collection $menuList): Collection
    {
        return $menuList->map(fn ($menuItem): MenuItemData => new MenuItemData($menuItem))
            ->when($this->hideUnavailable, fn (Collection $menuList) => $menuList->filter(fn (MenuItemData $menuItemData) => $menuItemData->mealtimeIsAvailable()));
    }
}
