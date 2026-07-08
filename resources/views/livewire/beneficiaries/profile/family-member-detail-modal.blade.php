@php
    $member = $this->member;
    $rows = [
        __('beneficiaries.family.field_relation') => $member->relation?->label(),
        __('beneficiaries.family.field_birth_date') => $member->birth_date
            ? $member->birth_date->translatedFormat('Y/m/d')
                .($member->birth_date->age !== null ? ' · '.trans_choice('beneficiaries.family_tree.age_years', $member->birth_date->age, ['count' => $member->birth_date->age]) : '')
            : null,
        __('beneficiaries.family.field_health_status') => $member->health_status,
        __('beneficiaries.family.field_education_status') => $member->education_status,
    ];
@endphp

<div class="p-6">
    <div class="mb-5 flex items-start gap-4">
        <span class="flex size-14 shrink-0 items-center justify-center rounded-full bg-primary-100 text-primary-700 ring-2 ring-primary-200 dark:bg-primary-500/20 dark:text-primary-200 dark:ring-primary-500/30">
            <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
            </svg>
        </span>
        <div class="min-w-0">
            <h2 class="truncate text-lg font-semibold text-gray-900 dark:text-white" title="{{ $member->name }}">{{ $member->name }}</h2>
            @if ($member->relation)
                <p class="mt-0.5 text-sm text-primary-600 dark:text-primary-300">{{ $member->relation->label() }}</p>
            @endif
        </div>
    </div>

    <dl class="divide-y divide-gray-100 rounded-(--radius-brand) border border-gray-100 dark:divide-white/10 dark:border-white/10">
        @foreach ($rows as $label => $value)
            <div class="flex items-start justify-between gap-4 px-4 py-2.5">
                <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                <dd class="text-end text-sm font-medium text-gray-900 dark:text-white">{{ filled($value) ? $value : __('common.dash') }}</dd>
            </div>
        @endforeach
    </dl>

    <div class="mt-6 flex items-center justify-end gap-3 border-t border-gray-100 pt-5 dark:border-white/10">
        <x-ui.button type="button" variant="ghost" wire:click="closeModal">
            {{ __('common.close') }}
        </x-ui.button>

        @can('update', $beneficiary)
            <x-ui.button
                type="button"
                variant="primary"
                x-on:click="$dispatch('openModal', { component: 'beneficiaries.profile.family-member-modal', arguments: { beneficiary: '{{ $beneficiary->hashid }}', memberId: {{ $member->id }} } })"
            >
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                </svg>
                {{ __('common.edit') }}
            </x-ui.button>
        @endcan
    </div>
</div>
