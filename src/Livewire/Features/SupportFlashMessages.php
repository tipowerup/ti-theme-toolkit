<?php

declare(strict_types=1);

namespace TiPowerUp\ThemeToolkit\Livewire\Features;

use Igniter\Flame\Flash\Message;
use Illuminate\Validation\ValidationException;
use Livewire\ComponentHook;
use Livewire\Features\SupportRedirects\Redirector;
use Livewire\Mechanisms\HandleRequests\HandleRequests;
use stdClass;

/**
 * SupportFlashMessages — Livewire component hook for displaying flash messages.
 *
 * Catches exceptions and dispatches them as flash messages. On every dehydrate
 * (Livewire response), checks for queued flash messages and emits them as
 * `flashMessageAdded` events for the frontend to display.
 */
final class SupportFlashMessages extends ComponentHook
{
    /**
     * Catch exceptions and flash them to the user (unless in production and not
     * a validation exception).
     *
     * @param  \Throwable  $e  The exception to handle
     * @param  callable  $stopPropagation  Callback to stop further propagation
     */
    public function exception($e, $stopPropagation): void
    {
        if (! config('app.debug') && ! $e instanceof ValidationException) {
            flash()->error($e->getMessage())->important();
            $stopPropagation();
        }
    }

    /**
     * Called after each Livewire request finishes. Dispatches any queued flash
     * messages to the component via the `flashMessageAdded` event.
     *
     * @param  object  $context  The Livewire context object
     */
    public function dehydrate($context): void
    {
        if (! app(HandleRequests::class)->isLivewireRequest()) {
            return;
        }

        if (isset($context->effects['returns']) && $this->hasRedirector($context->effects['returns'])) {
            return;
        }

        $messages = app('flash')->all();

        if ($messages->isNotEmpty()) {
            $this->component->dispatch('flashMessageAdded', $messages->map(fn (Message $message): stdClass => (object) $message->toArray())->all());
        }
    }

    protected function hasRedirector(array $returns): bool
    {
        return collect($returns)->filter(fn ($return): bool => $return instanceof Redirector)->isNotEmpty();
    }
}
