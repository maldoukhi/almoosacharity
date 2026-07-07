<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::guest')]
class ForgotPassword extends Component
{
    public string $email = '';

    public ?string $status = null;

    /**
     * Always show the same generic success message regardless of whether
     * the email matches an account, so this form can never be used to
     * enumerate registered addresses. Throttled at 3 attempts per minute,
     * keyed by email + client IP, matching the login form's pattern.
     */
    public function sendResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $throttleKey = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $this->addError('email', __('auth.throttle', [
                'seconds' => RateLimiter::availableIn($throttleKey),
            ]));

            return;
        }

        RateLimiter::hit($throttleKey, 60);

        Password::sendResetLink(['email' => $this->email]);

        $this->status = __('passwords.sent');
        $this->reset('email');
    }

    protected function throttleKey(): string
    {
        return Str::lower($this->email).'|'.request()->ip();
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}
