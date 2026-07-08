@php
    $channelOptions = collect($this->channels)->mapWithKeys(fn ($channel) => [$channel->value => $channel->label()]);
    $statusOptions = collect($this->statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()]);
    $sourceOptions = collect([
        'broadcast' => __('reports.messages.source_broadcast'),
        'notification' => __('reports.messages.source_notification'),
    ]);
@endphp

<div class="space-y-6" x-data="{ reasonOpen: false, reasonText: '', bodyOpen: false, bodyText: '' }">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('reports.messages.title') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('reports.messages.subtitle') }}</p>
        </div>

        @can('reports.export')
            <div class="flex items-center gap-2">
                <x-ui.button wire:click="exportExcel" variant="secondary" size="sm">
                    {{ __('reports.actions.export_excel') }}
                </x-ui.button>
                <x-ui.button wire:click="exportPdf" variant="ghost" size="sm">
                    {{ __('reports.actions.export_pdf') }}
                </x-ui.button>
            </div>
        @endcan
    </div>

    <x-ui.card>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <x-ui.input :label="__('reports.filters.from')" name="from" type="date" wire:model.live="from" />
            <x-ui.input :label="__('reports.filters.to')" name="to" type="date" wire:model.live="to" />

            <x-ui.select
                :label="__('reports.messages.filter_channel')"
                name="channel"
                wire:model.live="channel"
                :placeholder="__('common.all')"
                :options="$channelOptions"
            />

            <x-ui.select
                :label="__('reports.messages.filter_status')"
                name="status"
                wire:model.live="status"
                :placeholder="__('common.all')"
                :options="$statusOptions"
            />

            <x-ui.select
                :label="__('reports.messages.filter_source')"
                name="source"
                wire:model.live="source"
                :placeholder="__('common.all')"
                :options="$sourceOptions"
            />
        </div>
    </x-ui.card>

    <x-ui.card>
        <x-slot:header>
            <div class="flex flex-wrap items-center gap-x-6 gap-y-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                <span>{{ __('reports.messages.total_count', ['count' => $this->totals['count']]) }}</span>
                <span class="text-status-approved">{{ __('reports.messages.total_sent', ['count' => $this->totals['sent']]) }}</span>
                <span class="text-status-rejected">{{ __('reports.messages.total_failed', ['count' => $this->totals['failed']]) }}</span>
                <span class="text-status-review">{{ __('reports.messages.total_pending', ['count' => $this->totals['pending']]) }}</span>
                <span class="text-secondary-700 dark:text-secondary-300">{{ __('reports.messages.total_sms', ['count' => $this->totals['sms']]) }}</span>
                <span class="text-accent-700 dark:text-accent-300">{{ __('reports.messages.total_whatsapp', ['count' => $this->totals['whatsapp']]) }}</span>
            </div>
        </x-slot:header>

        <div wire:loading.flex wire:target="from, to, channel, status, source" class="hidden flex-col gap-2" style="display: none">
            <x-ui.skeleton height="3rem" />
            <x-ui.skeleton height="3rem" />
            <x-ui.skeleton height="3rem" />
        </div>

        <div wire:loading.remove wire:target="from, to, channel, status, source" class="space-y-4">
            <x-ui.table>
                <thead>
                    <tr>
                        <x-ui.table.th>{{ __('reports.messages.column_date') }}</x-ui.table.th>
                        <x-ui.table.th>{{ __('reports.messages.column_recipient') }}</x-ui.table.th>
                        <x-ui.table.th>{{ __('reports.messages.column_channel') }}</x-ui.table.th>
                        <x-ui.table.th>{{ __('reports.messages.column_status') }}</x-ui.table.th>
                        <x-ui.table.th>{{ __('reports.messages.column_source') }}</x-ui.table.th>
                        <x-ui.table.th>{{ __('reports.messages.column_sender') }}</x-ui.table.th>
                        <x-ui.table.th>{{ __('reports.messages.column_excerpt') }}</x-ui.table.th>
                        @can('messages.broadcast')
                            <x-ui.table.th align="end">{{ __('common.actions') }}</x-ui.table.th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse ($this->rows as $row)
                        <tr wire:key="message-{{ $row->id }}" class="transition duration-150 hover:bg-gray-50 dark:hover:bg-white/5">
                            <x-ui.table.td class="tabular-nums">
                                {{ optional($row->sent_at ?? $row->created_at)->format('Y-m-d H:i') }}
                            </x-ui.table.td>
                            <x-ui.table.td>
                                <div class="flex flex-col">
                                    <span class="tabular-nums">{{ $row->recipient }}</span>
                                    @if ($this->report->recipientName($row))
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $this->report->recipientName($row) }}</span>
                                    @endif
                                </div>
                            </x-ui.table.td>
                            <x-ui.table.td>
                                <x-ui.badge color="secondary">{{ $row->channel->label() }}</x-ui.badge>
                            </x-ui.table.td>
                            <x-ui.table.td>
                                @if ($row->status === \App\Enums\MessageStatus::Failed && filled($row->error))
                                    <button
                                        type="button"
                                        @click="reasonText = @js($row->error); reasonOpen = true"
                                        title="{{ __('reports.messages.view_reason') }}"
                                        class="inline-flex items-center gap-1"
                                    >
                                        <x-ui.badge :color="$row->status->color()">
                                            {{ $row->status->label() }}
                                            <svg class="ms-0.5 size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                            </svg>
                                        </x-ui.badge>
                                    </button>
                                @else
                                    <x-ui.badge :color="$row->status->color()">{{ $row->status->label() }}</x-ui.badge>
                                @endif
                            </x-ui.table.td>
                            <x-ui.table.td>{{ $this->report->sourceLabel($row) }}</x-ui.table.td>
                            <x-ui.table.td>{{ $this->report->senderName($row) }}</x-ui.table.td>
                            <x-ui.table.td>
                                <button
                                    type="button"
                                    @click="bodyText = @js($row->body); bodyOpen = true"
                                    title="{{ __('reports.messages.view_message') }}"
                                    class="max-w-[18rem] truncate text-start text-primary-700 underline-offset-2 hover:underline dark:text-primary-300"
                                >
                                    {{ Illuminate\Support\Str::limit($row->body, 60) }}
                                </button>
                            </x-ui.table.td>
                            @can('messages.broadcast')
                                <x-ui.table.td align="end">
                                    @if ($row->status === \App\Enums\MessageStatus::Failed)
                                        <x-ui.button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            wire:click="resend({{ $row->id }})"
                                            wire:confirm="{{ __('reports.messages.confirm_resend') }}"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                            </svg>
                                            {{ __('reports.messages.resend') }}
                                        </x-ui.button>
                                    @endif
                                </x-ui.table.td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ __('reports.pdf.no_data') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-ui.table>

            <div>{{ $this->rows->links() }}</div>
        </div>
    </x-ui.card>

    {{-- Full failure reason (opened by clicking a failed row's badge) --}}
    <div
        x-show="reasonOpen"
        x-cloak
        class="fixed inset-0 z-[70] overflow-y-auto"
        role="dialog"
        aria-modal="true"
        x-on:keydown.escape.window="reasonOpen = false"
    >
        <div class="absolute inset-0 bg-primary-950/60 backdrop-blur-sm" @click="reasonOpen = false"></div>

        <div class="flex min-h-dvh items-center justify-center p-4">
            <div class="relative w-full max-w-lg overflow-hidden rounded-(--radius-brand) bg-white shadow-xl dark:bg-primary-950 dark:ring-1 dark:ring-white/10">
                <div class="flex items-center gap-2.5 border-b border-gray-100 px-6 py-4 dark:border-white/10">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-status-rejected/10 text-status-rejected">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                    </span>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('reports.messages.column_reason') }}</h3>
                </div>

                <div class="px-6 py-5">
                    <p class="rounded-(--radius-brand) bg-gray-50 p-4 text-sm leading-6 break-words whitespace-pre-line text-gray-800 dark:bg-white/5 dark:text-gray-100" dir="ltr" x-text="reasonText"></p>
                </div>

                <div class="flex items-center justify-end border-t border-gray-100 px-6 py-4 dark:border-white/10">
                    <x-ui.button type="button" variant="ghost" size="sm" @click="reasonOpen = false">
                        {{ __('common.close') }}
                    </x-ui.button>
                </div>
            </div>
        </div>
    </div>

    {{-- Full message body (opened by clicking a row's excerpt) --}}
    <div
        x-show="bodyOpen"
        x-cloak
        class="fixed inset-0 z-[70] overflow-y-auto"
        role="dialog"
        aria-modal="true"
        x-on:keydown.escape.window="bodyOpen = false"
    >
        <div class="absolute inset-0 bg-primary-950/60 backdrop-blur-sm" @click="bodyOpen = false"></div>

        <div class="flex min-h-dvh items-center justify-center p-4">
            <div class="relative w-full max-w-lg overflow-hidden rounded-(--radius-brand) bg-white shadow-xl dark:bg-primary-950 dark:ring-1 dark:ring-white/10">
                <div class="flex items-center gap-2.5 border-b border-gray-100 px-6 py-4 dark:border-white/10">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-secondary-100 text-secondary-700 dark:bg-secondary-500/20 dark:text-secondary-200">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                        </svg>
                    </span>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('reports.messages.message_title') }}</h3>
                </div>

                <div class="px-6 py-5">
                    <p class="max-h-80 overflow-y-auto rounded-(--radius-brand) bg-gray-50 p-4 text-sm leading-6 break-words whitespace-pre-line text-gray-800 dark:bg-white/5 dark:text-gray-100" x-text="bodyText"></p>
                </div>

                <div class="flex items-center justify-end border-t border-gray-100 px-6 py-4 dark:border-white/10">
                    <x-ui.button type="button" variant="ghost" size="sm" @click="bodyOpen = false">
                        {{ __('common.close') }}
                    </x-ui.button>
                </div>
            </div>
        </div>
    </div>
</div>
