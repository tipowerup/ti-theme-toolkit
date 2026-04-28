<?php

declare(strict_types=1);

namespace TiPowerUp\ThemeToolkit\Livewire;

use Igniter\Main\Traits\ConfigurableComponent;
use Igniter\Main\Traits\UsesPage;
use Igniter\User\Actions\LoginCustomer;
use Igniter\User\Facades\Auth;
use Igniter\User\Models\Customer;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;
use TiPowerUp\ThemeToolkit\Livewire\Forms\LoginForm;

/**
 * Customer login component shared by every theme using the toolkit.
 *
 * The class owns the full login flow (validation, action dispatch,
 * intended-redirect handling). Each theme's auto-loader registers this class
 * under the active theme's view namespace, so `<theme-ns>::login` works
 * out-of-the-box. The blade view at `resources/views/livewire/login.blade.php`
 * is the only theme-specific surface.
 *
 * Themes that need custom admin descriptors or extra mount-time behavior can
 * extend this class and override {@see componentMeta()} or {@see render()}
 * — Livewire registration is last-writer-wins, so the theme's subclass
 * overrides the toolkit's registration.
 */
class Login extends Component
{
    use ConfigurableComponent;
    use UsesPage;

    public LoginForm $form;

    public bool $registrationAllowed = true;

    public string $redirectPage = 'account.account';

    public bool $intendedRedirect = true;

    #[Url]
    public string $redirect = '';

    public static function componentMeta(): array
    {
        return [
            'code' => '{ns}::login',
            'name' => '{lang}::default.component_login_title',
            'description' => '{lang}::default.component_login_desc',
        ];
    }

    public function defineProperties(): array
    {
        return [
            'redirectPage' => [
                'label' => 'Page to redirect to after login.',
                'type' => 'select',
                'options' => self::getThemePageOptions(...),
                'validationRule' => 'required|regex:/^[a-z0-9\-_\.]+$/i',
            ],
            'intendedRedirect' => [
                'label' => 'Force redirect to the previous page, this will override the redirect page.',
                'type' => 'switch',
                'default' => true,
                'validationRule' => 'required|boolean',
            ],
        ];
    }

    public function render()
    {
        return view($this->resolveViewNamespace().'::livewire.login');
    }

    public function mount(): void
    {
        $this->registrationAllowed = (bool) setting('allow_registration', true);

        if ($this->intendedRedirect && $intendedRedirectUrl = $this->getRedirectIntendedUrl()) {
            redirect()->setIntendedUrl($intendedRedirectUrl);
        }
    }

    public function customer(): ?Customer
    {
        if (! Auth::check()) {
            return null;
        }

        return Auth::getUser();
    }

    public function onLogin(): void
    {
        $this->form->validate();

        rescue(function (): void {
            resolve(LoginCustomer::class, [
                'credentials' => $this->form->except('remember'),
                'remember' => $this->form->remember,
            ])->handle();

            if ($this->redirect !== '') {
                $this->redirect(page_url($this->redirect));
            } else {
                $this->intendedRedirect
                    ? $this->redirectIntended(page_url($this->redirectPage))
                    : $this->redirect(page_url($this->redirectPage));
            }
        }, function (Throwable $e): never {
            throw ValidationException::withMessages(['form.email' => $e->getMessage()]);
        });
    }

    /**
     * Resolves the active theme's view namespace at render time so the same
     * toolkit class works for any theme — `controller()->getTheme()->getName()`
     * matches the view namespace registered in `loadViewsFrom(...)`.
     */
    protected function resolveViewNamespace(): string
    {
        return controller()?->getTheme()?->getName() ?? app('tipowerup.theme.viewNamespace');
    }

    protected function getRedirectIntendedUrl(): ?string
    {
        if (redirect()->getIntendedUrl()) {
            return null;
        }

        $previousUrl = url()->previous();
        if (! $previousUrl || $previousUrl === url()->current() || ! str_starts_with($previousUrl, url('/'))) {
            return null;
        }

        return $previousUrl;
    }
}
