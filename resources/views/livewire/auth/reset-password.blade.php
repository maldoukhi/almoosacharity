<div>
    <div class="mb-6 text-center">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('auth.reset_password_title') }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('auth.reset_password_subtitle') }}</p>
    </div>

    <form wire:submit="resetPassword" class="space-y-5">
        <x-ui.input
            :label="__('auth.email')"
            name="email"
            type="email"
            wire:model="email"
            autofocus
            autocomplete="username"
        />

        <x-ui.input
            :label="__('auth.password')"
            name="password"
            type="password"
            wire:model="password"
            autocomplete="new-password"
        />

        <x-ui.input
            :label="__('auth.password_confirmation')"
            name="password_confirmation"
            type="password"
            wire:model="password_confirmation"
            autocomplete="new-password"
        />

        <x-ui.button type="submit" variant="primary" size="lg" wire:target="resetPassword" class="w-full">
            {{ __('auth.reset_password_button') }}
        </x-ui.button>
    </form>
</div>
