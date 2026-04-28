<?php

declare(strict_types=1);

namespace TiPowerUp\ThemeToolkit\Livewire;

use Exception;
use Igniter\Main\Traits\ConfigurableComponent;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ResetPassword extends Component
{
    use ConfigurableComponent;

    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|min:6')]
    public string $password = '';

    #[Validate('required|same:password')]
    public string $password_confirmation = '';

    public string $resetCode = '';

    public string $message = '';

    public static function componentMeta(): array
    {
        return [
            'code' => '{ns}::reset-password',
            'name' => 'Reset Password',
            'description' => 'Allows customers to reset their password',
        ];
    }

    public function defineProperties(): array
    {
        return [];
    }

    public function mount(): void
    {
        $this->resetCode = $this->getResetCode();
    }

    protected function getResetCode(): string
    {
        return controller()->param('code', '');
    }

    public function onForgotPassword(): void
    {
        $this->validate(['email' => 'required|email']);

        try {
            $data = post();
            $data['email'] = $this->email;

            $response = app('Igniter\User\Components\ResetPassword')->onRequestResetPassword($data);

            if (isset($response['status']) && $response['status'] === 'success') {
                $this->message = $response['message'] ?? 'Password reset link has been sent to your email.';
                $this->reset('email');
            }
        } catch (Exception $e) {
            $this->addError('email', $e->getMessage());
        }
    }

    public function onResetPassword(): void
    {
        $this->validate([
            'password' => 'required|min:6',
            'password_confirmation' => 'required|same:password',
        ]);

        try {
            $data = post();
            $data['code'] = $this->resetCode;
            $data['password'] = $this->password;
            $data['password_confirmation'] = $this->password_confirmation;

            $response = app('Igniter\User\Components\ResetPassword')->onResetPassword($data);

            if (isset($response['status']) && $response['status'] === 'success') {
                $this->message = $response['message'] ?? 'Password has been reset successfully.';
                $this->reset(['password', 'password_confirmation']);
            }
        } catch (Exception $e) {
            $this->addError('password', $e->getMessage());
        }
    }

    public function render()
    {
        return view($this->resolveViewNamespace().'::livewire.reset-password');
    }

    protected function resolveViewNamespace(): string
    {
        return controller()?->getTheme()?->getName() ?? app('tipowerup.theme.viewNamespace');
    }
}
