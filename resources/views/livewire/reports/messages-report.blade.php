@php
    $channelOptions = collect($this->channels)->mapWithKeys(fn ($channel) => [$channel->value => $channel->label()]);
    $statusOptions = collect($this->statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()]);
    $sourceOptions = collect([
        'broadcast' => __('reports.messages.source_broadcast'),
        'notification' => __('reports.messages.source_notification'),
    ]);
@endphp

<div class="space-y-6">
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
                        <x-ui.table.th>{{ __('reports.messages.column_reason') }}</x-ui.table.th>
                        <x-ui.table.th>{{ __('reports.messages.column_source') }}</x-ui.table.th>
                        <x-ui.table.th>{{ __('reports.messages.column_sender') }}</x-ui.table.th>
                        <x-ui.table.th>{{ __('reports.messages.column_excerpt') }}</x-ui.table.th>
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
                                <x-ui.badge :color="$row->status->color()">{{ $row->status->label() }}</x-ui.badge>
                            </x-ui.table.td>
                            <x-ui.table.td>
                                @if ($row->status === \App\Enums\MessageStatus::Failed && filled($row->error))
                                    <span class="text-xs text-status-rejected" title="{{ $row->error }}">{{ Illuminate\Support\Str::limit($row->error, 80) }}</span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">{{ __('common.dash') }}</span>
                                @endif
                            </x-ui.table.td>
                            <x-ui.table.td>{{ $this->report->sourceLabel($row) }}</x-ui.table.td>
                            <x-ui.table.td>{{ $this->report->senderName($row) }}</x-ui.table.td>
                            <x-ui.table.td>{{ Illuminate\Support\Str::limit($row->body, 60) }}</x-ui.table.td>
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
</div>
