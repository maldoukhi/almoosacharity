@php
    use App\Enums\RecurrenceFrequency;

    $frequencyOptions = collect(RecurrenceFrequency::cases())->mapWithKeys(fn ($frequency) => [$frequency->value => $frequency->label()]);

    $frequencyText = function ($plan) {
        if ($plan->frequency === RecurrenceFrequency::CustomMonths) {
            return trans_choice('recurring_aids.interval_every_months', (int) $plan->interval_months, ['count' => (int) $plan->interval_months]);
        }

        return $plan->frequency->label();
    };
@endphp

<div class="space-y-6">
    @unless ($embedded)
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('recurring_aids.index_title') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('recurring_aids.index_subtitle') }}</p>
            </div>

            <x-ui.button href="{{ route('aids.index') }}" variant="ghost">
                {{ __('common.back') }}
            </x-ui.button>
        </div>
    @else
        <div>
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('recurring_aids.section_title') }}</h2>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ __('recurring_aids.section_subtitle') }}</p>
        </div>
    @endunless

    <x-ui.card>
        @if ($this->plans->isEmpty())
            <x-ui.empty-state :title="__('recurring_aids.empty_title')" :description="__('recurring_aids.empty_description')">
                <x-slot:icon>
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                </x-slot:icon>
            </x-ui.empty-state>
        @else
            <x-ui.table>
                <thead>
                    <tr>
                        @unless ($beneficiaryId)
                            <x-ui.table.th>{{ __('recurring_aids.col_beneficiary') }}</x-ui.table.th>
                        @endunless
                        <x-ui.table.th>{{ __('recurring_aids.col_program') }}</x-ui.table.th>
                        <x-ui.table.th>{{ __('recurring_aids.col_frequency') }}</x-ui.table.th>
                        <x-ui.table.th>{{ __('recurring_aids.col_next_run') }}</x-ui.table.th>
                        <x-ui.table.th>{{ __('recurring_aids.col_lead_days') }}</x-ui.table.th>
                        <x-ui.table.th>{{ __('recurring_aids.col_status') }}</x-ui.table.th>
                        <x-ui.table.th align="end">{{ __('recurring_aids.col_generated') }}</x-ui.table.th>
                        <x-ui.table.th align="end">{{ __('common.actions') }}</x-ui.table.th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @foreach ($this->plans as $plan)
                        <tr wire:key="plan-{{ $plan->id }}" class="transition duration-150 hover:bg-gray-50 dark:hover:bg-white/5">
                            @unless ($beneficiaryId)
                                <x-ui.table.td class="text-gray-900 dark:text-white">
                                    @if ($plan->aid?->beneficiary)
                                        <a href="{{ route('admin.beneficiaries.show', $plan->aid->beneficiary) }}" wire:navigate class="text-primary-700 underline-offset-2 hover:underline dark:text-primary-300">
                                            {{ $plan->aid->beneficiary->full_name }}
                                        </a>
                                    @else
                                        {{ __('common.dash') }}
                                    @endif
                                </x-ui.table.td>
                            @endunless
                            <x-ui.table.td>{{ $plan->aid?->program?->name ?? __('common.dash') }}</x-ui.table.td>
                            <x-ui.table.td>{{ $frequencyText($plan) }}</x-ui.table.td>
                            <x-ui.table.td class="tabular-nums text-gray-500 dark:text-gray-400">
                                {{ $plan->next_run_on?->translatedFormat('Y/m/d') }}
                            </x-ui.table.td>
                            <x-ui.table.td class="tabular-nums">
                                {{ trans_choice('recurring_aids.lead_days_value', (int) $plan->lead_days, ['count' => (int) $plan->lead_days]) }}
                            </x-ui.table.td>
                            <x-ui.table.td>
                                <x-ui.badge :color="$plan->is_active ? 'secondary' : 'gray'">
                                    {{ $plan->is_active ? __('recurring_aids.status_active') : __('recurring_aids.status_paused') }}
                                </x-ui.badge>
                            </x-ui.table.td>
                            <x-ui.table.td align="end" class="tabular-nums">
                                <button
                                    type="button"
                                    wire:click="viewSeries({{ $plan->id }})"
                                    class="font-medium text-primary-700 underline-offset-2 hover:underline dark:text-primary-300"
                                >
                                    {{ trans_choice('recurring_aids.generated_count', (int) $plan->aids_count, ['count' => (int) $plan->aids_count]) }}
                                </button>
                            </x-ui.table.td>
                            <x-ui.table.td align="end">
                                <div class="flex items-center justify-end gap-1">
                                    <x-ui.button variant="ghost" size="sm" wire:click="viewSeries({{ $plan->id }})">
                                        {{ __('recurring_aids.action_view_series') }}
                                    </x-ui.button>

                                    @can('aids.update')
                                        <x-ui.button variant="ghost" size="sm" wire:click="togglePause({{ $plan->id }})">
                                            {{ $plan->is_active ? __('recurring_aids.action_pause') : __('recurring_aids.action_resume') }}
                                        </x-ui.button>

                                        <x-ui.button variant="ghost" size="sm" wire:click="openEdit({{ $plan->id }})">
                                            {{ __('recurring_aids.action_edit') }}
                                        </x-ui.button>
                                    @endcan

                                    @can('aids.delete')
                                        <x-ui.button
                                            variant="danger"
                                            size="sm"
                                            data-confirm="{{ __('recurring_aids.confirm_delete') }}"
                                            x-on:click="uiConfirm($el.dataset.confirm, () => $wire.delete({{ $plan->id }}), { danger: true })"
                                        >
                                            {{ __('recurring_aids.action_delete') }}
                                        </x-ui.button>
                                    @endcan
                                </div>
                            </x-ui.table.td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>

            <div class="mt-4">
                {{ $this->plans->links() }}
            </div>
        @endif
    </x-ui.card>

    {{-- Edit modal --}}
    @if ($editingPlanId !== null)
        <div class="fixed inset-0 z-[70] overflow-y-auto" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-primary-950/60 backdrop-blur-sm" wire:click="closeEdit"></div>

            <div class="flex min-h-dvh items-center justify-center p-4">
                <div class="relative w-full max-w-lg overflow-hidden rounded-(--radius-brand) bg-white shadow-xl dark:bg-primary-950 dark:ring-1 dark:ring-white/10">
                    <div class="border-b border-gray-100 px-6 py-4 dark:border-white/10">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('recurring_aids.edit_modal.title') }}</h3>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ __('recurring_aids.edit_modal.subtitle') }}</p>
                    </div>

                    <form wire:submit="saveEdit" class="space-y-5 px-6 py-5">
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <x-ui.select
                                :label="__('aids.recurrence.frequency_label')"
                                name="editFrequency"
                                wire:model.live="editFrequency"
                                :options="$frequencyOptions"
                            />

                            @if ($editFrequency === RecurrenceFrequency::CustomMonths->value)
                                <x-ui.input
                                    :label="__('aids.recurrence.interval_months')"
                                    name="editIntervalMonths"
                                    type="number"
                                    min="1"
                                    max="60"
                                    wire:model="editIntervalMonths"
                                />
                            @endif

                            <x-ui.input
                                :label="__('aids.recurrence.starts_on')"
                                name="editStartsOn"
                                type="date"
                                wire:model="editStartsOn"
                                :hint="__('aids.recurrence.starts_on_hint')"
                            />

                            <x-ui.input
                                :label="__('aids.recurrence.due_on')"
                                name="editDueOn"
                                type="date"
                                wire:model="editDueOn"
                                :hint="__('aids.recurrence.due_on_hint')"
                            />

                            <x-ui.input
                                :label="__('aids.recurrence.ends_on')"
                                name="editEndsOn"
                                type="date"
                                wire:model="editEndsOn"
                                :hint="__('aids.recurrence.ends_on_hint')"
                            />

                            <x-ui.input
                                :label="__('aids.recurrence.lead_days')"
                                name="editLeadDays"
                                type="number"
                                min="0"
                                max="365"
                                wire:model="editLeadDays"
                                :hint="__('aids.recurrence.lead_days_hint')"
                            />
                        </div>

                        <div>
                            <x-ui.input
                                :label="__('aids.recurrence.title_template')"
                                name="editTitleTemplate"
                                type="text"
                                maxlength="255"
                                wire:model.live.debounce.400ms="editTitleTemplate"
                                :hint="__('aids.recurrence.title_template_hint')"
                            />
                            <div class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1">
                                <button type="button" wire:click="useDefaultTitleTemplate" class="text-xs font-medium text-primary-600 hover:text-primary-700 dark:text-primary-300">
                                    {{ __('aids.recurrence.title_use_default') }}
                                </button>
                                @if ($this->editTitlePreview)
                                    <span class="text-xs text-gray-400 dark:text-gray-500">·</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('aids.recurrence.title_preview') }}
                                        <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $this->editTitlePreview }}</span>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <x-ui.toggle
                            :label="__('aids.recurrence.active')"
                            :description="__('aids.recurrence.active_hint')"
                            name="editActive"
                            wire:model="editActive"
                        />

                        <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5 dark:border-white/10">
                            <x-ui.button type="button" variant="ghost" wire:click="closeEdit">
                                {{ __('common.cancel') }}
                            </x-ui.button>
                            <x-ui.button type="submit" variant="primary" wire:target="saveEdit" wire:loading.attr="disabled">
                                {{ __('recurring_aids.edit_modal.save') }}
                            </x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- View series modal --}}
    @if ($viewingSeriesPlanId !== null && $this->seriesPlan)
        <div class="fixed inset-0 z-[70] overflow-y-auto" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-primary-950/60 backdrop-blur-sm" wire:click="closeSeries"></div>

            <div class="flex min-h-dvh items-center justify-center p-4">
                <div class="relative w-full max-w-2xl overflow-hidden rounded-(--radius-brand) bg-white shadow-xl dark:bg-primary-950 dark:ring-1 dark:ring-white/10">
                    <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-6 py-4 dark:border-white/10">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('recurring_aids.series_modal.title') }}</h3>
                            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ __('recurring_aids.series_modal.subtitle') }}</p>
                        </div>
                        <x-ui.badge :color="$this->seriesPlan->is_active ? 'secondary' : 'gray'">
                            {{ $this->seriesPlan->is_active ? __('recurring_aids.status_active') : __('recurring_aids.status_paused') }}
                        </x-ui.badge>
                    </div>

                    <div class="max-h-[60vh] overflow-y-auto px-6 py-5">
                        @if ($this->seriesAids->isEmpty())
                            <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('recurring_aids.series_modal.empty') }}</p>
                        @else
                            <x-ui.table>
                                <thead>
                                    <tr>
                                        <x-ui.table.th>{{ __('recurring_aids.col_reference') }}</x-ui.table.th>
                                        <x-ui.table.th>{{ __('recurring_aids.col_title') }}</x-ui.table.th>
                                        <x-ui.table.th>{{ __('aids.field_status') }}</x-ui.table.th>
                                        <x-ui.table.th align="end">{{ __('recurring_aids.col_amount') }}</x-ui.table.th>
                                        <x-ui.table.th>{{ __('recurring_aids.col_created_at') }}</x-ui.table.th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                                    @foreach ($this->seriesAids as $seriesAid)
                                        <tr wire:key="series-aid-{{ $seriesAid->id }}">
                                            <x-ui.table.td>
                                                <a href="{{ route('aids.show', $seriesAid) }}" wire:navigate class="font-mono font-medium text-primary-700 underline-offset-2 hover:underline dark:text-primary-300">
                                                    {{ $seriesAid->reference }}
                                                </a>
                                            </x-ui.table.td>
                                            <x-ui.table.td class="text-gray-900 dark:text-white">
                                                {{ $seriesAid->title ?: __('common.dash') }}
                                            </x-ui.table.td>
                                            <x-ui.table.td>
                                                <x-ui.badge :color="$seriesAid->status->color()">{{ $seriesAid->status->label() }}</x-ui.badge>
                                            </x-ui.table.td>
                                            <x-ui.table.td align="end" class="tabular-nums">
                                                @if ($seriesAid->type === \App\Enums\AidType::Cash)
                                                    {{ number_format((float) $seriesAid->amount, 2) }} {{ __('aids.currency_sar') }}
                                                @else
                                                    {{ trans_choice('aids.items_count', $seriesAid->items_count, ['count' => $seriesAid->items_count]) }}
                                                @endif
                                            </x-ui.table.td>
                                            <x-ui.table.td class="tabular-nums text-gray-500 dark:text-gray-400">
                                                {{ $seriesAid->created_at?->translatedFormat('Y/m/d') }}
                                            </x-ui.table.td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </x-ui.table>
                        @endif
                    </div>

                    <div class="flex flex-col gap-3 border-t border-gray-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-white/10">
                        @can('aids.update')
                            <div class="flex items-center gap-2">
                                <x-ui.button
                                    type="button"
                                    variant="secondary"
                                    data-confirm="{{ __('recurring_aids.series_modal.confirm_add') }}"
                                    x-on:click="uiConfirm($el.dataset.confirm, () => $wire.addManual({{ $this->seriesPlan->id }}))"
                                >
                                    {{ __('recurring_aids.series_modal.add_button') }}
                                </x-ui.button>
                                <span class="hidden text-xs text-gray-500 sm:inline dark:text-gray-400">{{ __('recurring_aids.series_modal.add_hint') }}</span>
                            </div>
                        @else
                            <span></span>
                        @endcan

                        <div class="flex items-center justify-end gap-3">
                            @can('aids.update')
                                @if ($this->seriesPlan->is_active)
                                    <x-ui.button
                                        type="button"
                                        variant="ghost"
                                        data-confirm="{{ __('recurring_aids.confirm_pause') }}"
                                        x-on:click="uiConfirm($el.dataset.confirm, () => $wire.pauseFromSeries({{ $this->seriesPlan->id }}))"
                                    >
                                        {{ __('recurring_aids.series_modal.pause_button') }}
                                    </x-ui.button>
                                @endif
                            @endcan

                            <x-ui.button type="button" variant="primary" wire:click="closeSeries">
                                {{ __('common.close') }}
                            </x-ui.button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
