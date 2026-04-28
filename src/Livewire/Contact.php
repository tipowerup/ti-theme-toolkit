<?php

declare(strict_types=1);

namespace TiPowerUp\ThemeToolkit\Livewire;

use Igniter\Local\Models\Location;
use Igniter\Main\Traits\ConfigurableComponent;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Contact extends Component
{
    use ConfigurableComponent;

    #[Validate('required|max:128')]
    public string $subject = '';

    #[Validate('required|email:filter|max:96')]
    public string $email = '';

    #[Validate('required|min:2|max:255')]
    public string $fullName = '';

    #[Validate('required')]
    public string $telephone = '';

    #[Validate('required|max:1500')]
    public string $message = '';

    public bool $sent = false;

    public array $subjects = [
        'General Enquiry',
        'Comment',
        'Technical Issues',
    ];

    public static function componentMeta(): array
    {
        return [
            'code' => '{ns}::contact',
            'name' => 'Contact Form',
            'description' => 'Displays a contact form',
        ];
    }

    public function defineProperties(): array
    {
        return [];
    }

    public function send(): void
    {
        $this->validate();

        // TODO: Implement email sending logic
        // This should integrate with TastyIgniter's mail system

        $this->sent = true;
        $this->reset(['subject', 'email', 'fullName', 'telephone', 'message']);
    }

    public function resetForm(): void
    {
        $this->sent = false;
        $this->reset();
    }

    public function render()
    {
        return view($this->resolveViewNamespace().'::livewire.contact', [
            'locationDefault' => Location::getDefault(),
        ]);
    }

    protected function resolveViewNamespace(): string
    {
        return controller()?->getTheme()?->getName() ?? app('tipowerup.theme.viewNamespace');
    }
}
