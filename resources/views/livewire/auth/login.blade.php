<div>
    <div class="mb-6 text-center">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('auth.login_title') }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('auth.login_subtitle') }}</p>
    </div>

    <form wire:submit="login" class="space-y-5">
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
            autocomplete="current-password"
        />

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                <input
                    type="checkbox"
                    wire:model="remember"
                    class="rounded border-gray-300 text-primary focus:ring-2 focus:ring-primary-500/30 dark:border-white/20 dark:bg-transparent"
                >
                {{ __('auth.remember_me') }}
            </label>

            <a
                href="{{ route('password.request') }}"
                wire:navigate
                class="text-sm font-medium text-primary-700 transition duration-150 hover:text-primary-800 dark:text-primary-300 dark:hover:text-primary-200"
            >
                {{ __('auth.forgot_password') }}
            </a>
        </div>

        <x-ui.button type="submit" variant="primary" size="lg" wire:target="login" class="w-full">
            {{ __('auth.login_button') }}
        </x-ui.button>
    </form>
</div>
