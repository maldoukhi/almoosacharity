@php
    $tabs = [
        'basic' => __('beneficiaries.tab.basic'),
        'family' => __('beneficiaries.tab.family'),
        'housing_income' => __('beneficiaries.tab.housing_income'),
        'bank' => __('beneficiaries.tab.bank'),
        'documents' => __('beneficiaries.tab.documents'),
        'activity' => __('beneficiaries.tab.activity'),
    ];

    $basicInfo = [
        __('beneficiaries.field_id_type') => $beneficiary->id_type?->label(),
        __('beneficiaries.field_national_id') => $beneficiary->national_id,
        __('beneficiaries.field_nationality') => \App\Support\Countries::nationalityName($beneficiary->nationality),
        __('beneficiaries.field_birth_date') => $beneficiary->birth_date?->translatedFormat('Y/m/d'),
        __('beneficiaries.field_gender') => $beneficiary->gender?->label(),
        __('beneficiaries.field_marital_status') => $beneficiary->marital_status?->label(),
        __('beneficiaries.field_family_members_count') => $beneficiary->family_members_count,
        __('beneficiaries.field_mobile') => $beneficiary->mobile,
        __('beneficiaries.field_occupation') => $beneficiary->occupation,
        __('beneficiaries.field_employer') => $beneficiary->employer,
        __('beneficiaries.field_health_status') => $beneficiary->health_status,
        __('beneficiaries.field_special_needs') => $beneficiary->special_needs,
    ];

    $housingInfo = [
        __('beneficiaries.field_housing_type') => $beneficiary->housing_type?->label(),
        __('beneficiaries.field_rent_amount') => $beneficiary->housing_type?->value === 'rented' ? $beneficiary->rent_amount : null,
        __('beneficiaries.field_national_address') => $beneficiary->national_address,
        __('beneficiaries.field_city') => $beneficiary->city,
        __('beneficiaries.field_district') => $beneficiary->district,
        __('beneficiaries.field_monthly_income') => $beneficiary->monthly_income,
    ];
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $beneficiary->full_name }}</h1>
                <x-ui.badge :color="$beneficiary->status->color()">{{ $beneficiary->status->label() }}</x-ui.badge>
            </div>

            <div class="mt-2 flex flex-wrap gap-1.5">
                @forelse ($beneficiary->categories as $category)
                    <x-ui.badge color="accent">{{ $category->name }}</x-ui.badge>
                @empty
                    <span class="text-sm text-gray-400 dark:text-gray-500">{{ __('common.dash') }}</span>
                @endforelse
            </div>
        </div>

        <div class="flex items-center gap-3">
            @can('update', $beneficiary)
                <x-ui.button href="{{ route('admin.beneficiaries.edit', $beneficiary) }}" variant="primary">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                    </svg>
                    {{ __('common.edit') }}
                </x-ui.button>
            @endcan

            <x-ui.button href="{{ route('admin.beneficiaries.index') }}" variant="ghost">
                {{ __('common.back') }}
            </x-ui.button>
        </div>
    </div>

    <x-ui.card>
        <x-ui.tabs :tabs="$tabs" :active="$activeTab" wireClick="setTab" />

        <div class="pt-5">
            @if ($activeTab === 'basic')
                <dl class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($basicInfo as $label => $value)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $value ?: __('common.dash') }}</dd>
                        </div>
                    @endforeach
                </dl>
            @elseif ($activeTab === 'family')
                <div
                    x-data="{
                        view: 'tree',
                        scale: 1,
                        panX: 0,
                        panY: 0,
                        dragging: false,
                        startX: 0,
                        startY: 0,
                        zoomIn() { this.scale = Math.min(2, Math.round((this.scale + 0.15) * 100) / 100) },
                        zoomOut() { this.scale = Math.max(0.6, Math.round((this.scale - 0.15) * 100) / 100) },
                        resetView() { this.scale = 1; this.panX = 0; this.panY = 0 },
                        startDrag(e) {
                            this.dragging = true
                            this.startX = e.clientX - this.panX
                            this.startY = e.clientY - this.panY
                        },
                        onDrag(e) {
                            if (! this.dragging) return
                            this.panX = e.clientX - this.startX
                            this.panY = e.clientY - this.startY
                        },
                        endDrag() { this.dragging = false },
                        onWheel(e) {
                            const delta = e.deltaY > 0 ? -0.1 : 0.1
                            this.scale = Math.min(2, Math.max(0.6, Math.round((this.scale + delta) * 100) / 100))
                        },
                    }"
                    class="space-y-4"
                >
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="inline-flex rounded-(--radius-brand) border border-gray-200 p-1 dark:border-white/10" role="tablist" aria-label="{{ __('beneficiaries.family_tree.view_switch_label') }}">
                            <button
                                type="button"
                                role="tab"
                                @click="view = 'tree'"
                                :aria-selected="view === 'tree'"
                                :class="view === 'tree' ? 'bg-primary text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white'"
                                class="rounded-[calc(var(--radius-brand)-0.25rem)] px-3.5 py-1.5 text-sm font-medium transition-colors duration-200 ease-out focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                            >
                                {{ __('beneficiaries.family_tree.view_tree') }}
                            </button>
                            <button
                                type="button"
                                role="tab"
                                @click="view = 'table'"
                                :aria-selected="view === 'table'"
                                :class="view === 'table' ? 'bg-primary text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white'"
                                class="rounded-[calc(var(--radius-brand)-0.25rem)] px-3.5 py-1.5 text-sm font-medium transition-colors duration-200 ease-out focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                            >
                                {{ __('beneficiaries.family_tree.view_table') }}
                            </button>
                        </div>

                        <div class="flex items-center gap-1" x-show="view === 'tree' && {{ $beneficiary->familyMembers->isNotEmpty() ? 'true' : 'false' }}" x-cloak>
                            <button
                                type="button"
                                @click="zoomOut()"
                                title="{{ __('beneficiaries.family_tree.zoom_out') }}"
                                class="rounded-full p-2 text-gray-500 transition duration-150 ease-out hover:bg-gray-100 hover:text-gray-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500 dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-white"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
                                </svg>
                                <span class="sr-only">{{ __('beneficiaries.family_tree.zoom_out') }}</span>
                            </button>

                            <button
                                type="button"
                                @click="resetView()"
                                title="{{ __('beneficiaries.family_tree.reset_view') }}"
                                class="rounded-full p-2 text-gray-500 transition duration-150 ease-out hover:bg-gray-100 hover:text-gray-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500 dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-white"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                <span class="sr-only">{{ __('beneficiaries.family_tree.reset_view') }}</span>
                            </button>

                            <button
                                type="button"
                                @click="zoomIn()"
                                title="{{ __('beneficiaries.family_tree.zoom_in') }}"
                                class="rounded-full p-2 text-gray-500 transition duration-150 ease-out hover:bg-gray-100 hover:text-gray-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500 dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-white"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                <span class="sr-only">{{ __('beneficiaries.family_tree.zoom_in') }}</span>
                            </button>
                        </div>
                    </div>

                    <div x-show="view === 'tree'" x-cloak>
                        @if ($beneficiary->familyMembers->isEmpty())
                            <x-ui.empty-state :title="__('beneficiaries.family_tree.empty_title')" :description="__('beneficiaries.family_tree.empty_description')" />
                        @else
                            <div
                                class="relative h-[32rem] touch-none overflow-hidden rounded-(--radius-brand) border border-gray-100 bg-gray-50/70 select-none dark:border-white/10 dark:bg-white/[0.02]"
                                :class="dragging ? 'cursor-grabbing' : 'cursor-grab'"
                                @pointerdown="startDrag($event)"
                                @pointermove.window="onDrag($event)"
                                @pointerup.window="endDrag()"
                                @pointerleave="endDrag()"
                                @wheel.prevent="onWheel($event)"
                            >
                                <div
                                    class="absolute inset-0 origin-center transition-transform duration-150 ease-out motion-reduce:transition-none"
                                    :style="`transform: translate(${panX}px, ${panY}px) scale(${scale})`"
                                >
                                    <svg class="absolute inset-0 h-full w-full" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                                        @foreach ($this->familyTreeNodes as $node)
                                            <line
                                                x1="50" y1="50" x2="{{ $node['x'] }}" y2="{{ $node['y'] }}"
                                                class="stroke-primary-200 dark:stroke-primary-800"
                                                stroke-width="0.6"
                                                vector-effect="non-scaling-stroke"
                                            />
                                        @endforeach
                                    </svg>

                                    {{-- المستفيد: العقدة المركزية --}}
                                    <div
                                        class="absolute flex w-28 -translate-x-1/2 -translate-y-1/2 flex-col items-center gap-1.5 text-center"
                                        style="left: 50%; top: 50%; animation: fade-in-up .3s ease-out both"
                                    >
                                        <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-primary text-white shadow-(--shadow-card) ring-4 ring-primary-100 dark:ring-primary-900/40">
                                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                        </span>
                                        <span class="max-w-[7rem] truncate text-sm font-semibold text-gray-900 dark:text-white" title="{{ $beneficiary->full_name }}">{{ $beneficiary->full_name }}</span>
                                        <span class="text-[11px] font-medium text-primary-600 dark:text-primary-300">{{ __('beneficiaries.family_tree.center_label') }}</span>
                                    </div>

                                    @foreach ($this->familyTreeNodes as $index => $node)
                                        <div
                                            wire:key="family-tree-node-{{ $node['id'] }}"
                                            class="absolute flex w-24 -translate-x-1/2 -translate-y-1/2 flex-col items-center gap-1 text-center"
                                            style="left: {{ $node['x'] }}%; top: {{ $node['y'] }}%; animation: fade-in-up .3s ease-out both; animation-delay: {{ min($index + 1, 10) * 60 }}ms"
                                        >
                                            <span
                                                @class([
                                                    'flex h-11 w-11 shrink-0 items-center justify-center rounded-full shadow-(--shadow-card) ring-2',
                                                    'bg-accent-100 text-accent-700 ring-accent-200 dark:bg-accent-500/20 dark:text-accent-200 dark:ring-accent-500/30' => $node['tier'] === 'top',
                                                    'bg-secondary-100 text-secondary-700 ring-secondary-200 dark:bg-secondary-500/20 dark:text-secondary-200 dark:ring-secondary-500/30' => $node['tier'] === 'side',
                                                    'bg-primary-100 text-primary-700 ring-primary-200 dark:bg-primary-500/20 dark:text-primary-200 dark:ring-primary-500/30' => $node['tier'] === 'bottom',
                                                    'bg-gray-100 text-gray-600 ring-gray-200 dark:bg-white/10 dark:text-gray-300 dark:ring-white/10' => $node['tier'] === 'other',
                                                ])
                                            >
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                                </svg>
                                            </span>
                                            <span class="max-w-[6rem] truncate text-xs font-semibold text-gray-900 dark:text-white" title="{{ $node['name'] }}">{{ $node['name'] }}</span>
                                            <span class="max-w-[6rem] truncate text-[11px] text-gray-500 dark:text-gray-400">
                                                {{ $node['relation'] }}
                                                @if ($node['age'] !== null)
                                                    &middot; {{ trans_choice('beneficiaries.family_tree.age_years', $node['age'], ['count' => $node['age']]) }}
                                                @endif
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div x-show="view === 'table'" x-cloak>
                        <livewire:beneficiaries.profile.family-members :beneficiary="$beneficiary" :wire:key="'family-'.$beneficiary->id" />
                    </div>
                </div>
            @elseif ($activeTab === 'housing_income')
                <div class="space-y-6">
                    <dl class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($housingInfo as $label => $value)
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                                <dd class="mt-1 text-sm tabular-nums text-gray-900 dark:text-white">{{ $value ?: __('common.dash') }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    <div class="border-t border-gray-100 pt-5 dark:border-white/10">
                        <livewire:beneficiaries.profile.income-sources :beneficiary="$beneficiary" :wire:key="'income-'.$beneficiary->id" />
                    </div>
                </div>
            @elseif ($activeTab === 'bank')
                <livewire:beneficiaries.profile.bank-panel :beneficiary="$beneficiary" :wire:key="'bank-'.$beneficiary->id" />
            @elseif ($activeTab === 'documents')
                <livewire:beneficiaries.profile.documents :beneficiary="$beneficiary" :wire:key="'documents-'.$beneficiary->id" />
            @elseif ($activeTab === 'activity')
                <livewire:beneficiaries.profile.activity-log :beneficiary="$beneficiary" :wire:key="'activity-'.$beneficiary->id" />
            @endif
        </div>
    </x-ui.card>
</div>
