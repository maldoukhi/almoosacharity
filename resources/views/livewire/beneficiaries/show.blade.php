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
        __('beneficiaries.field_nationality') => $beneficiary->nationality,
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
                <livewire:beneficiaries.profile.family-members :beneficiary="$beneficiary" :wire:key="'family-'.$beneficiary->id" />
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
