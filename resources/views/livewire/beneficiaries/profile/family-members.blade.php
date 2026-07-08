<div class="space-y-5">
    <div class="flex items-center justify-between">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('beneficiaries.family.title') }}</h2>

        @can('update', $beneficiary)
            <x-ui.button
                type="button"
                variant="secondary"
                size="sm"
                x-on:click="$dispatch('openModal', { component: 'beneficiaries.profile.family-member-modal', arguments: { beneficiary: {{ $beneficiary->id }} } })"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ __('beneficiaries.family.add_button') }}
            </x-ui.button>
        @endcan
    </div>

    @if ($this->members->isEmpty())
        <x-ui.empty-state :title="__('beneficiaries.family.empty_title')" :description="__('beneficiaries.family.empty_description')" />
    @else
        <x-ui.table>
            <thead>
                <tr>
                    <x-ui.table.th>{{ __('beneficiaries.family.field_name') }}</x-ui.table.th>
                    <x-ui.table.th>{{ __('beneficiaries.family.field_relation') }}</x-ui.table.th>
                    <x-ui.table.th>{{ __('beneficiaries.family.field_birth_date') }}</x-ui.table.th>
                    <x-ui.table.th>{{ __('beneficiaries.family.field_health_status') }}</x-ui.table.th>
                    <x-ui.table.th>{{ __('beneficiaries.family.field_education_status') }}</x-ui.table.th>
                    @can('update', $beneficiary)
                        <x-ui.table.th align="end">{{ __('common.actions') }}</x-ui.table.th>
                    @endcan
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                @foreach ($this->members as $member)
                    <tr wire:key="family-member-{{ $member->id }}" class="transition duration-150 hover:bg-gray-50 dark:hover:bg-white/5">
                        <x-ui.table.td class="font-medium text-gray-900 dark:text-white">{{ $member->name }}</x-ui.table.td>
                        <x-ui.table.td>{{ $member->relation?->label() }}</x-ui.table.td>
                        <x-ui.table.td>{{ $member->birth_date?->translatedFormat('Y/m/d') ?? __('common.dash') }}</x-ui.table.td>
                        <x-ui.table.td>{{ $member->health_status ?: __('common.dash') }}</x-ui.table.td>
                        <x-ui.table.td>{{ $member->education_status ?: __('common.dash') }}</x-ui.table.td>
                        @can('update', $beneficiary)
                            <x-ui.table.td align="end">
                                <div class="flex items-center justify-end gap-1">
                                    <x-ui.button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        x-on:click="$dispatch('openModal', { component: 'beneficiaries.profile.family-member-modal', arguments: { beneficiary: {{ $beneficiary->id }}, memberId: {{ $member->id }} } })"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                        <span class="sr-only">{{ __('common.edit') }}</span>
                                    </x-ui.button>

                                    <x-ui.button
                                        type="button"
                                        variant="danger"
                                        size="sm"
                                        wire:click="delete({{ $member->id }})"
                                        wire:confirm="{{ __('beneficiaries.family.confirm_delete') }}"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                        <span class="sr-only">{{ __('common.delete') }}</span>
                                    </x-ui.button>
                                </div>
                            </x-ui.table.td>
                        @endcan
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table>
    @endif
</div>
