<div>
    <div class="mb-6 text-center">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('auth.forgot_password_title') }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('auth.forgot_password_subtitle') }}</p>
    </div>

    @if ($status)
        <div class="mb-5 rounded-(--radius-brand) bg-status-approved/10 px-4 py-3 text-sm text-status-approved">
            {{ $status }}
        </div>
    @endif

    <form wire:submit="sendResetLink" class="space-y-5">
        <x-ui.input
            :label="__('auth.email')"
            name="email"
            type="email"
            wire:model="email"
            autofocus
            autocomplete="username"
        />

        <x-ui.button type="submit" variant="primary" size="lg" wire:target="sendResetLink" class="w-full">
            {{ __('auth.send_reset_link') }}
        </x-ui.button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
        <a
            href="{{ route('login') }}"
            wire:navigate
            class="font-medium text-primary-700 transition duration-150 hover:text-primary-800 dark:text-primary-300 dark:hover:text-primary-200"
        >
            {{ __('auth.back_to_login') }}
        </a>
    </p>
</div>
