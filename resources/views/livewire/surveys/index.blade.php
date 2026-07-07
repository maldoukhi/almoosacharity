<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('surveys.index_title') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.index_subtitle') }}</p>
        </div>

        @can('create', \App\Models\Survey::class)
            <x-ui.button href="{{ route('admin.surveys.create') }}" variant="primary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ __('surveys.create_button') }}
            </x-ui.button>
        @endcan
    </div>

    @if ($this->surveys->isEmpty())
        <x-ui.empty-state :title="__('surveys.empty_title')" :description="__('surveys.empty_description')">
            <x-slot:icon>
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664M6.75 7.5h.75m-.75 3h.75m-.75 3h.75m-3.75 6.75h13.5a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664" />
                </svg>
            </x-slot:icon>
        </x-ui.empty-state>
    @else
        <x-ui.table>
            <thead>
                <tr>
                    <x-ui.table.th>{{ __('surveys.field_title') }}</x-ui.table.th>
                    <x-ui.table.th>{{ __('surveys.field_scope') }}</x-ui.table.th>
                    <x-ui.table.th align="center">{{ __('surveys.field_questions_count') }}</x-ui.table.th>
                    <x-ui.table.th align="center">{{ __('surveys.field_responses_count') }}</x-ui.table.th>
                    <x-ui.table.th>{{ __('surveys.field_status') }}</x-ui.table.th>
                    <x-ui.table.th align="end">{{ __('common.actions') }}</x-ui.table.th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                @foreach ($this->surveys as $survey)
                    <tr class="transition duration-150 hover:bg-gray-50 dark:hover:bg-white/5" wire:key="survey-{{ $survey->id }}">
                        <x-ui.table.td class="font-medium text-gray-900 dark:text-white">
                            {{ $survey->title }}
                            @if ($survey->description)
                                <p class="mt-0.5 max-w-sm truncate text-xs font-normal text-gray-500 dark:text-gray-400">{{ $survey->description }}</p>
                            @endif
                        </x-ui.table.td>
                        <x-ui.table.td>
                            <x-ui.badge color="accent">{{ $survey->scope->label() }}</x-ui.badge>
                            @if ($survey->program)
                                <span class="ms-1 text-xs text-gray-500 dark:text-gray-400">{{ $survey->program->name }}</span>
                            @endif
                        </x-ui.table.td>
                        <x-ui.table.td align="center" class="tabular-nums">{{ $survey->questions_count }}</x-ui.table.td>
                        <x-ui.table.td align="center" class="tabular-nums">{{ $survey->responses_count }}</x-ui.table.td>
                        <x-ui.table.td>
                            @can('update', $survey)
                                <button
                                    type="button"
                                    wire:click="toggleActive({{ $survey->id }})"
                                    wire:confirm="{{ $survey->is_active ? __('surveys.confirm_toggle_deactivate') : __('surveys.confirm_toggle_activate') }}"
                                    class="cursor-pointer"
                                >
                                    <x-ui.badge :color="$survey->is_active ? 'approved' : 'gray'">
                                        {{ $survey->is_active ? __('surveys.status_active') : __('surveys.status_inactive') }}
                                    </x-ui.badge>
                                </button>
                            @else
                                <x-ui.badge :color="$survey->is_active ? 'approved' : 'gray'">
                                    {{ $survey->is_active ? __('surveys.status_active') : __('surveys.status_inactive') }}
                                </x-ui.badge>
                            @endcan
                        </x-ui.table.td>
                        <x-ui.table.td align="end">
                            <div class="flex items-center justify-end gap-1">
                                @can('viewResults', $survey)
                                    <x-ui.button href="{{ route('admin.surveys.results', $survey) }}" variant="ghost" size="sm">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                        </svg>
                                        <span class="sr-only">{{ __('surveys.results_title') }}</span>
                                    </x-ui.button>
                                @endcan

                                @can('update', $survey)
                                    <x-ui.button href="{{ route('admin.surveys.edit', $survey) }}" variant="ghost" size="sm">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                        <span class="sr-only">{{ __('common.edit') }}</span>
                                    </x-ui.button>
                                @endcan

                                @can('delete', $survey)
                                    <x-ui.button
                                        variant="danger"
                                        size="sm"
                                        wire:click="delete({{ $survey->id }})"
                                        wire:confirm="{{ __('surveys.confirm_delete') }}"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                        <span class="sr-only">{{ __('common.delete') }}</span>
                                    </x-ui.button>
                                @endcan
                            </div>
                        </x-ui.table.td>
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table>
    @endif
</div>
