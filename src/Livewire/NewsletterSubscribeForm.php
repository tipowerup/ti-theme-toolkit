<?php

declare(strict_types=1);

namespace TiPowerUp\ThemeToolkit\Livewire;

use Livewire\Component;

class NewsletterSubscribeForm extends Component
{
    public string $email = '';

    public bool $subscribed = false;

    public string $message = '';

    protected function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    public function subscribe(): void
    {
        $this->validate();

        // TODO: integrate with a real newsletter provider.

        $this->subscribed = true;
        $this->message = 'Thank you for subscribing! Please check your email to confirm.';
        $this->reset('email');
    }

    public function render()
    {
        return view($this->resolveViewNamespace().'::livewire.newsletter-subscribe-form');
    }

    protected function resolveViewNamespace(): string
    {
        return controller()?->getTheme()?->getName() ?? app('tipowerup.theme.viewNamespace');
    }
}
