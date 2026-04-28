<?php

declare(strict_types=1);

namespace TiPowerUp\ThemeToolkit\Livewire;

use Igniter\Main\Traits\ConfigurableComponent;
use Igniter\Main\Traits\UsesPage;
use Igniter\Socialite\Classes\ProviderManager;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Socialite extends Component
{
    use ConfigurableComponent;
    use UsesPage;

    public string $errorPage = 'account.login';

    public string $successPage = 'account.account';

    public bool $confirm = false;

    public ?string $email = null;

    public array $links = [];

    public static function componentMeta(): array
    {
        return [
            'code' => '{ns}::socialite',
            'name' => 'Socialite Login',
            'description' => 'Allows customers to login via social media',
        ];
    }

    public function defineProperties(): array
    {
        return [
            'errorPage' => [
                'label' => 'The error page',
                'type' => 'select',
                'options' => self::getThemePageOptions(...),
                'validationRule' => 'required|regex:/^[a-z0-9\-_\.]+$/i',
            ],
            'successPage' => [
                'label' => 'The success page',
                'type' => 'select',
                'options' => self::getThemePageOptions(...),
                'validationRule' => 'required|regex:/^[a-z0-9\-_\.]+$/i',
            ],
            'confirm' => [
                'label' => 'Display email confirmation form',
                'type' => 'switch',
            ],
        ];
    }

    public function render(): View
    {
        return view($this->resolveViewNamespace().'::livewire.socialite');
    }

    public function mount(): void
    {
        $this->links = $this->loadLinks();
    }

    public function onConfirmEmail(): ?RedirectResponse
    {
        $manager = resolve(ProviderManager::class);

        throw_unless($providerData = $manager->getProviderData(), ValidationException::withMessages([
            'email' => 'Missing social provider data',
        ]));

        $this->validate([
            'email' => 'required|email:filter|max:96|unique:customers,email',
        ], [], [
            'email' => lang('lang:igniter.user::default.reset.label_email'),
        ]);

        $providerData->user->email = $this->email;

        $manager->setProviderData($providerData);

        return $manager->completeCallback();
    }

    protected function loadLinks(): array
    {
        if (! class_exists(ProviderManager::class)) {
            return [];
        }

        return resolve(ProviderManager::class)
            ->listProviderLinks()
            ->mapWithKeys(fn ($url, $code): array => [
                $code => $url.'?success='.page_url($this->successPage).'&error='.page_url($this->errorPage),
            ])
            ->all();
    }

    protected function resolveViewNamespace(): string
    {
        return controller()?->getTheme()?->getName() ?? app('tipowerup.theme.viewNamespace');
    }
}
