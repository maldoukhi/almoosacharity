<div class="space-y-5">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('beneficiaries.aids.title') }}</h2>

    @if ($this->aids->isEmpty())
        <x-ui.empty-state :title="__('beneficiaries.aids.empty_title')" :description="__('beneficiaries.aids.empty_description')" />
    @else
        <x-ui.table>
            <thead>
                <tr>
                    <x-ui.table.th>{{ __('beneficiaries.aids.field_reference') }}</x-ui.table.th>
                    <x-ui.table.th>{{ __('beneficiaries.aids.field_title') }}</x-ui.table.th>
                    <x-ui.table.th>{{ __('beneficiaries.aids.field_program') }}</x-ui.table.th>
                    <x-ui.table.th>{{ __('beneficiaries.aids.field_type') }}</x-ui.table.th>
                    <x-ui.table.th align="end">{{ __('beneficiaries.aids.field_amount') }}</x-ui.table.th>
                    <x-ui.table.th>{{ __('beneficiaries.aids.field_status') }}</x-ui.table.th>
                    <x-ui.table.th>{{ __('beneficiaries.aids.field_created_at') }}</x-ui.table.th>
                    <x-ui.table.th align="end">{{ __('common.actions') }}</x-ui.table.th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                @foreach ($this->aids as $aid)
                    <tr wire:key="beneficiary-aid-{{ $aid->id }}" class="transition duration-150 hover:bg-gray-50 dark:hover:bg-white/5">
                        <x-ui.table.td class="font-mono text-xs text-gray-500 dark:text-gray-400" dir="ltr">{{ $aid->reference }}</x-ui.table.td>
                        <x-ui.table.td class="font-medium text-gray-900 dark:text-white">{{ $aid->title ?: __('common.dash') }}</x-ui.table.td>
                        <x-ui.table.td>{{ $aid->program?->name ?: __('common.dash') }}</x-ui.table.td>
                        <x-ui.table.td>
                            <x-ui.badge :color="$aid->type === \App\Enums\AidType::Cash ? 'primary' : 'secondary'">{{ $aid->type->label() }}</x-ui.badge>
                        </x-ui.table.td>
                        <x-ui.table.td align="end" class="tabular-nums">
                            @if ($aid->type === \App\Enums\AidType::Cash && $aid->amount !== null)
                                {{ number_format((float) $aid->amount, 2) }} <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('aids.currency_sar') }}</span>
                            @else
                                {{ __('common.dash') }}
                            @endif
                        </x-ui.table.td>
                        <x-ui.table.td>
                            <x-ui.badge :color="$aid->status->color()">{{ $aid->status->label() }}</x-ui.badge>
                        </x-ui.table.td>
                        <x-ui.table.td class="tabular-nums text-gray-500 dark:text-gray-400">{{ $aid->created_at?->translatedFormat('Y/m/d') }}</x-ui.table.td>
                        <x-ui.table.td align="end">
                            <x-ui.button href="{{ route('aids.show', $aid) }}" variant="ghost" size="sm">
                                {{ __('common.view') }}
                            </x-ui.button>
                        </x-ui.table.td>
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table>
    @endif
</div>
