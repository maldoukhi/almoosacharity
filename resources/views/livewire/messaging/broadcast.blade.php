@php
    $channelOptions = collect(\App\Enums\MessageChannel::cases())->mapWithKeys(fn ($case) => [
        $case->value => $case->label(),
    ]);

    $categoryOptions = $this->categories->mapWithKeys(fn ($category) => [$category->id => $category->name]);

    $statusOptions = collect(\App\Enums\BeneficiaryStatus::cases())->mapWithKeys(fn ($status) => [
        $status->value => $status->label(),
    ]);

    $cityOptions = collect($this->cities)->mapWithKeys(fn ($city) => [$city => $city]);

    $maxLength = $channel === 'sms' ? 480 : 1000;
    $isEmail = $channel === 'email';
@endphp

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('messaging.broadcast.title') }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('messaging.broadcast.subtitle') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
        {{-- Compose --}}
        <div class="space-y-6 lg:col-span-2">
            <x-ui.card>
                <x-slot:header>{{ __('messaging.broadcast.channel_label') }}</x-slot:header>

                <div class="grid grid-cols-2 gap-3">
                    @foreach ($channelOptions as $value => $label)
                        <label
                            wire:key="channel-{{ $value }}"
                            @class([
                                'flex cursor-pointer flex-col items-center gap-2 rounded-(--radius-brand) border-2 px-4 py-4 text-sm font-medium transition duration-150',
                                'border-primary-500 bg-primary-50 text-primary-800 dark:bg-primary-500/10 dark:text-primary-100' => $channel === $value,
                                'border-gray-200 text-gray-600 hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5' => $channel !== $value,
                            ])
                        >
                            <input type="radio" wire:model.live="channel" value="{{ $value }}" class="sr-only" />
                            {{ $label }}
                        </label>
                    @endforeach
                </div>

                <div class="mt-4">
                    <x-ui.select
                        :label="__('messaging.broadcast.template_label')"
                        name="selectedTemplate"
                        wire:model="selectedTemplate"
                        :placeholder="__('messaging.broadcast.template_placeholder')"
                        :options="$this->templateOptions"
                    />

                    <div class="mt-2 flex justify-end">
                        <x-ui.button variant="ghost" size="sm" wire:click="applyTemplate" wire:target="applyTemplate">
                            {{ __('messaging.broadcast.apply_template') }}
                        </x-ui.button>
                    </div>
                </div>

                <div class="mt-4">
                    <label for="body" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
                        {{ __('messaging.broadcast.body_label') }}
                    </label>

                    <textarea
                        id="body"
                        wire:model.live="body"
                        rows="6"
                        maxlength="{{ $maxLength }}"
                        placeholder="{{ __('messaging.broadcast.body_placeholder') }}"
                        class="block w-full rounded-(--radius-brand) border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm transition duration-200 ease-out focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-white/10 dark:bg-primary-950/30 dark:text-gray-100"
                    ></textarea>

                    <div class="mt-1.5 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                        <span>{{ __('messaging.broadcast.hint_variable') }}</span>
                        <span class="tabular-nums">{{ mb_strlen($body) }}/{{ $maxLength }}</span>
                    </div>

                    @error('body')
                        <p class="mt-1.5 text-xs text-status-rejected">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-4 border-t border-gray-100 pt-4 dark:border-white/10">
                    <x-ui.toggle
                        wire:model.live="saveAsTemplate"
                        :label="__('messaging.broadcast.save_as_template')"
                    />

                    @if ($saveAsTemplate)
                        <div class="mt-3">
                            <x-ui.input
                                :label="__('messaging.broadcast.template_name_label')"
                                name="newTemplateName"
                                wire:model="newTemplateName"
                            />
                        </div>
                    @endif
                </div>

                {{-- Attachment (WhatsApp only — SMS is text-only). --}}
                @if ($this->channelSupportsAttachment())
                    <div class="mt-4 border-t border-gray-100 pt-4 dark:border-white/10">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
                            {{ __('messaging.broadcast.attachment_label') }}
                        </label>

                        <div class="flex flex-wrap items-center gap-3">
                            <label
                                class="inline-flex cursor-pointer items-center gap-2 rounded-(--radius-brand) border border-gray-300 bg-white px-3.5 py-2 text-sm font-medium text-gray-700 transition duration-150 hover:bg-gray-50 dark:border-white/10 dark:bg-primary-950/30 dark:text-gray-200 dark:hover:bg-white/5"
                            >
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                                </svg>
                                {{ __('messaging.broadcast.attachment_choose') }}
                                <input type="file" wire:model="attachment" accept=".pdf,.jpg,.jpeg,.png" class="sr-only" />
                            </label>

                            <span wire:loading wire:target="attachment" class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('messaging.broadcast.attachment_uploading') }}
                            </span>

                            @if ($attachment)
                                <span wire:loading.remove wire:target="attachment" class="inline-flex items-center gap-2 text-xs">
                                    <span class="font-medium text-status-approved">
                                        {{ __('messaging.broadcast.attachment_selected', ['name' => $attachment->getClientOriginalName()]) }}
                                    </span>
                                    <button
                                        type="button"
                                        wire:click="removeAttachment"
                                        class="font-medium text-status-rejected hover:underline"
                                    >
                                        {{ __('messaging.broadcast.attachment_remove') }}
                                    </button>
                                </span>
                            @endif
                        </div>

                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                            {{ $isEmail ? __('messaging.broadcast.attachment_hint_email') : __('messaging.broadcast.attachment_hint') }}
                        </p>

                        @error('attachment')
                            <p class="mt-1.5 text-xs text-status-rejected">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card>
                <x-slot:header>{{ $isEmail ? __('messaging.broadcast.manual_emails_label') : __('messaging.broadcast.manual_numbers_label') }}</x-slot:header>

                <textarea
                    id="manualNumbers" maxlength="4000"
                    wire:model.live.debounce.300ms="manualNumbers"
                    rows="4"
                    placeholder="{{ $isEmail ? __('messaging.broadcast.manual_emails_placeholder') : __('messaging.broadcast.manual_numbers_placeholder') }}"
                    dir="ltr"
                    class="block w-full rounded-(--radius-brand) border border-gray-300 bg-white px-3.5 py-2.5 text-start text-sm text-gray-900 shadow-sm transition duration-200 ease-out focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-white/10 dark:bg-primary-950/30 dark:text-gray-100"
                ></textarea>

                <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                    {{ $isEmail ? __('messaging.broadcast.manual_emails_hint') : __('messaging.broadcast.manual_numbers_hint') }}
                </p>

                <div class="mt-2 flex items-center gap-3 text-xs">
                    <span class="font-medium text-status-approved">
                        {{ $isEmail
                            ? __('messaging.broadcast.manual_emails_valid_count', ['count' => count($this->manualNumbersValid)])
                            : __('messaging.broadcast.manual_numbers_valid_count', ['count' => count($this->manualNumbersValid)]) }}
                    </span>

                    @if ($this->manualNumbersInvalid !== [])
                        <span class="font-medium text-status-rejected">
                            {{ $isEmail
                                ? __('messaging.broadcast.manual_emails_invalid_count', ['count' => count($this->manualNumbersInvalid)])
                                : __('messaging.broadcast.manual_numbers_invalid_count', ['count' => count($this->manualNumbersInvalid)]) }}
                        </span>
                    @endif
                </div>

                @error('manualNumbers')
                    <p class="mt-1.5 text-xs text-status-rejected">{{ $message }}</p>
                @enderror
            </x-ui.card>

            <x-ui.card>
                <x-slot:header>{{ __('messaging.broadcast.preview_title') }}</x-slot:header>

                <div class="rounded-(--radius-brand) bg-gray-50 p-4 dark:bg-white/5">
                    <p class="whitespace-pre-line text-sm text-gray-800 dark:text-gray-100">
                        {{ $this->preview !== '' ? $this->preview : __('messaging.broadcast.preview_empty') }}
                    </p>
                </div>

                <div class="mt-4 flex flex-col gap-2">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300">
                        {{ __('messaging.broadcast.recipients_breakdown', [
                            'beneficiaries' => $this->beneficiaryEligibleCount,
                            'manual' => count($this->manualNumbersValid),
                            'total' => $this->eligibleCount,
                        ]) }}
                    </p>

                    <x-ui.button
                        wire:click="confirmSend"
                        :disabled="$this->eligibleCount === 0 || trim($body) === '' || $this->manualNumbersInvalid !== []"
                    >
                        {{ __('messaging.broadcast.send_button', ['count' => $this->eligibleCount]) }}
                    </x-ui.button>

                    @if (! $isEmail && $this->excludedNoMobileCount > 0)
                        <p class="text-xs text-status-review">
                            {{ __('messaging.broadcast.excluded_no_mobile', ['count' => $this->excludedNoMobileCount]) }}
                        </p>
                    @endif
                </div>
            </x-ui.card>
        </div>

        {{-- Filters + recipients --}}
        <div class="space-y-6 lg:col-span-3">
            <x-ui.card>
                <x-slot:header>{{ __('messaging.broadcast.filters_title') }}</x-slot:header>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ui.input
                        :label="__('common.search')"
                        name="search"
                        wire:model.live.debounce.300ms="search"
                        :placeholder="__('beneficiaries.search_placeholder')"
                    />

                    <x-ui.select
                        :label="__('beneficiaries.filter_category')"
                        name="categoryFilter"
                        wire:model.live="categoryFilter"
                        :placeholder="__('common.all')"
                        :options="$categoryOptions"
                    />

                    <x-ui.select
                        :label="__('beneficiaries.filter_status')"
                        name="statusFilter"
                        wire:model.live="statusFilter"
                        :placeholder="__('common.all')"
                        :options="$statusOptions"
                    />

                    <x-ui.select
                        :label="__('beneficiaries.filter_city')"
                        name="cityFilter"
                        wire:model.live="cityFilter"
                        :placeholder="__('common.all')"
                        :options="$cityOptions"
                    />
                </div>

                <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-4 dark:border-white/10">
                    <x-ui.toggle
                        wire:model.live="selectAllFiltered"
                        :label="__('messaging.broadcast.select_all_filtered')"
                    />

                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
                        {{ __('messaging.broadcast.selected_count', ['count' => $this->beneficiaryEligibleCount]) }}
                    </span>
                </div>
            </x-ui.card>

            <x-ui.card>
                @if ($this->recipients->isEmpty())
                    <x-ui.empty-state :title="__('messaging.broadcast.empty_title')" :description="__('messaging.broadcast.empty_description')">
                        <x-slot:icon>
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                        </x-slot:icon>
                    </x-ui.empty-state>
                @else
                    <x-ui.table>
                        <thead>
                            <tr>
                                <x-ui.table.th></x-ui.table.th>
                                <x-ui.table.th>{{ __('beneficiaries.field_full_name') }}</x-ui.table.th>
                                <x-ui.table.th>{{ __('beneficiaries.field_mobile') }}</x-ui.table.th>
                                <x-ui.table.th>{{ __('beneficiaries.field_city') }}</x-ui.table.th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                            @foreach ($this->recipients as $recipient)
                                <tr wire:key="recipient-{{ $recipient->id }}" class="transition duration-150 hover:bg-gray-50 dark:hover:bg-white/5">
                                    <x-ui.table.td>
                                        <input
                                            type="checkbox"
                                            wire:click="toggleId({{ $recipient->id }})"
                                            @checked(in_array($recipient->id, $selectedIds, true))
                                            @disabled(blank($recipient->mobile))
                                            class="rounded border-gray-300 text-primary focus:ring-2 focus:ring-primary-500/30 disabled:opacity-40 dark:border-white/20 dark:bg-transparent"
                                        />
                                    </x-ui.table.td>
                                    <x-ui.table.td class="font-medium text-gray-900 dark:text-white">
                                        {{ $recipient->full_name }}
                                    </x-ui.table.td>
                                    <x-ui.table.td class="tabular-nums" dir="ltr">
                                        @if ($recipient->mobile)
                                            {{ $recipient->mobile }}
                                        @else
                                            <span class="text-status-review">{{ __('messaging.broadcast.no_mobile') }}</span>
                                        @endif
                                    </x-ui.table.td>
                                    <x-ui.table.td>{{ $recipient->city ?: __('common.dash') }}</x-ui.table.td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>

                    <div class="mt-4">
                        {{ $this->recipients->links() }}
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div>

    {{-- Confirm-before-send modal: recipients summary + message preview --}}
    @if ($showSendConfirm)
        <div class="fixed inset-0 z-[70] overflow-y-auto" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-primary-950/60 backdrop-blur-sm" wire:click="cancelSend"></div>

            <div class="flex min-h-dvh items-center justify-center p-4">
                <div class="relative w-full max-w-lg overflow-hidden rounded-(--radius-brand) bg-white shadow-xl dark:bg-primary-950 dark:ring-1 dark:ring-white/10">
                    <div class="flex items-start gap-3 border-b border-gray-100 px-6 py-4 dark:border-white/10">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-secondary-100 text-secondary-700 dark:bg-secondary-500/20 dark:text-secondary-200">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.126A59.768 59.768 0 0 1 21.485 12 59.77 59.77 0 0 1 3.27 20.876L5.999 12Zm0 0h7.5" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('messaging.broadcast.confirm_title') }}</h3>
                            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ __('messaging.broadcast.confirm_subtitle') }}</p>
                        </div>
                    </div>

                    <div class="px-6 py-5">
                        <dl class="divide-y divide-gray-100 rounded-(--radius-brand) border border-gray-100 dark:divide-white/10 dark:border-white/10">
                            <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('messaging.broadcast.field_channel') }}</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ \App\Enums\MessageChannel::from($channel)->label() }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('messaging.broadcast.confirm_recipients') }}</dt>
                                <dd class="text-sm font-semibold tabular-nums text-gray-900 dark:text-white">{{ $this->eligibleCount }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('messaging.broadcast.confirm_breakdown') }}</dt>
                                <dd class="text-sm text-gray-700 dark:text-gray-200">
                                    {{ __('messaging.broadcast.recipients_breakdown', [
                                        'beneficiaries' => $this->beneficiaryEligibleCount,
                                        'manual' => count($this->manualNumbersValid),
                                        'total' => $this->eligibleCount,
                                    ]) }}
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-4">
                            <p class="mb-1.5 text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('messaging.broadcast.confirm_message') }}</p>
                            <div class="max-h-40 overflow-y-auto rounded-(--radius-brand) bg-gray-50 p-4 dark:bg-white/5">
                                <p class="whitespace-pre-line text-sm text-gray-800 dark:text-gray-100">{{ $this->preview }}</p>
                            </div>
                        </div>

                        @if (! $isEmail && $this->excludedNoMobileCount > 0)
                            <p class="mt-3 text-xs text-status-review">
                                {{ __('messaging.broadcast.excluded_no_mobile', ['count' => $this->excludedNoMobileCount]) }}
                            </p>
                        @endif
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4 dark:border-white/10">
                        <x-ui.button type="button" variant="ghost" wire:click="cancelSend">
                            {{ __('common.cancel') }}
                        </x-ui.button>
                        <x-ui.button type="button" variant="primary" wire:click="send" wire:target="send" wire:loading.attr="disabled">
                            {{ __('messaging.broadcast.confirm_send_button', ['count' => $this->eligibleCount]) }}
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
