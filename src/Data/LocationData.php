<?php

declare(strict_types=1);

namespace TiPowerUp\ThemeToolkit\Data;

use Carbon\Carbon;
use Igniter\Cart\Classes\AbstractOrderType;
use Igniter\Cart\Classes\OrderTypes;
use Igniter\Cart\Models\Concerns\LocationAction;
use Igniter\Local\Classes\CoveredArea;
use Igniter\Local\Classes\WorkingSchedule;
use Igniter\Local\Facades\Location;
use Igniter\Local\Models\Location as LocationModel;
use Igniter\Local\Models\Review;
use Igniter\Local\Models\WorkingHour;
use Illuminate\Support\Collection;

final class LocationData
{
    public int $id;

    public string $name;

    public string $description;

    public string $permalink;

    public array $address;

    public bool $hasDelivery;

    public bool $hasCollection;

    public WorkingSchedule $openingSchedule;

    protected ?array $payments = null;

    public function __construct(public LocationModel|LocationAction $model)
    {
        $this->id = $model->getKey();
        $this->name = $model->getName();
        $this->description = $model->getDescription() ?? '';
        $this->permalink = $model->permalink_slug;
        $this->address = $model->getAddress();
        $this->hasDelivery = $model->hasDelivery();
        $this->hasCollection = $model->hasCollection();
        $this->openingSchedule = $model->newWorkingSchedule(LocationModel::OPENING);
    }

    /**
     * Create a LocationData instance for the currently active location.
     */
    public static function current(): self
    {
        $current = Location::current();

        return new self($current);
    }

    /**
     * Get the page URL for this location with the location slug injected.
     */
    public function url(string $page): string
    {
        return page_url($page, ['location' => $this->model->permalink_slug]);
    }

    /**
     * Get the distance from the user to this location (if available).
     *
     * @return mixed The distance value (unit depends on location settings)
     */
    public function distance(): mixed
    {
        return $this->model->distance;
    }

    /**
     * Get the location's media gallery as a collection.
     */
    public function gallery(): Collection
    {
        return $this->model->getGallery();
    }

    /**
     * Check if the location has a media gallery.
     */
    public function hasGallery(): bool
    {
        return $this->model->hasGallery();
    }

    /**
     * Check if the location has a thumbnail image.
     */
    public function hasThumb(): bool
    {
        return $this->model->hasMedia('thumb');
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function getThumb(array $options = [], ?string $tag = null): ?string
    {
        return $this->model->getThumbOrBlank($options, $tag);
    }

    /**
     * Get the current active order type (delivery or collection).
     */
    public function orderType(): ?AbstractOrderType
    {
        return Location::getOrderType();
    }

    /**
     * Get the last order time (closing time) for the current order type.
     */
    public function lastOrderTime(): Carbon
    {
        return Carbon::parse($this->orderType()->getSchedule()->getCloseTime());
    }

    /**
     * Get all available order types for this location.
     */
    public function orderTypes(): Collection
    {
        return $this->model->availableOrderTypes();
    }

    /**
     * Get the location's average review score.
     */
    public function reviewsScore(): float
    {
        return Review::calculateScoreForLocation($this->model);
    }

    /**
     * Get the total number of reviews for this location.
     */
    public function reviewsCount(): int
    {
        return (int) ($this->model?->reviews_count ?: 0);
    }

    /**
     * Get all delivery areas mapped into CoveredArea objects.
     */
    public function deliveryAreas(): Collection
    {
        return $this->model->listDeliveryAreas()->mapInto(CoveredArea::class);
    }

    /**
     * Get all available payment methods for this location.
     *
     * @return string[]
     */
    public function payments(): array
    {
        if (! is_null($this->payments)) {
            return $this->payments;
        }

        return $this->payments = $this->model->listAvailablePayments()->pluck('name')->all();
    }

    /**
     * Get working hours grouped by day name.
     */
    public function schedules(): Collection
    {
        return $this->model->getWorkingHours()->groupBy(fn ($model) => $model->day->isoFormat('dddd'));
    }

    /**
     * Get all order schedule types available for this location (opening hours, delivery, collection).
     *
     * @return array<int|string, array<string, mixed>>
     */
    public function scheduleTypes(): mixed
    {
        return collect(resolve(OrderTypes::class)->listOrderTypes())
            ->prepend(['name' => 'igniter.local::default.text_opening'], LocationModel::OPENING)
            ->all();
    }

    /**
     * Get working hours arranged by schedule type and day of week.
     *
     * @return array<string, array<string, array<int, array<string, mixed>>>>
     */
    public function scheduleItems(): array
    {
        $scheduleItems = [];
        foreach ($this->scheduleTypes() as $code => $definition) {
            $schedule = $this->model->createScheduleItem($code);
            foreach ((new WorkingHour)->getWeekDaysOptions() as $index => $day) {
                $hours = array_map(function (array $hour): array {
                    $hour['open'] = now()->setTimeFromTimeString($hour['open'])->isoFormat(lang('system::lang.moment.time_format'));
                    $hour['close'] = now()->setTimeFromTimeString($hour['close'])->isoFormat(lang('system::lang.moment.time_format'));

                    return $hour;
                }, array_get($schedule->getHours(), $index, []));

                $scheduleItems[$code][$day] = array_filter($hours, fn (array $hour): bool => (bool) $hour['status']);
            }
        }

        return $scheduleItems;
    }

    /**
     * Get the opening schedule (working hours for normal operations).
     */
    public function openingSchedule(): WorkingSchedule
    {
        return $this->openingSchedule;
    }
}
