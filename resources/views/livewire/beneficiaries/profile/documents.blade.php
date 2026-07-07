@php
    $documentTypeOptions = collect(\App\Enums\DocumentType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]);
@endphp

<div class="space-y-6">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('beneficiaries.documents.title') }}</h2>

    @can('update', $beneficiary)
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-ui.select
                :label="__('beneficiaries.documents.field_type')"
                name="documentType"
                wire:model="documentType"
                :options="$documentTypeOptions"
            />

            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
                    {{ __('beneficiaries.documents.upload_label') }}
                </label>

                <label
                    for="document-upload"
                    class="group flex cursor-pointer items-center justify-center gap-3 rounded-(--radius-brand) border-2 border-dashed border-gray-300 bg-white px-4 py-4 text-center transition duration-150 ease-out hover:border-primary-400 hover:bg-primary-50/40 dark:border-white/10 dark:bg-primary-950/20 dark:hover:border-primary-600"
                >
                    <svg class="h-6 w-6 shrink-0 text-gray-400 transition duration-150 group-hover:text-primary-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3.75 3.75 0 0 1 4.132 6.331A5.25 5.25 0 0 1 17.25 19.5H6.75Z" />
                    </svg>

                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        <span wire:loading.remove wire:target="upload">{{ __('beneficiaries.documents.upload_hint') }}</span>
                        <span wire:loading wire:target="upload" class="inline-flex items-center gap-2 text-primary-600 dark:text-primary-300">
                            <span class="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent motion-reduce:animate-none" aria-hidden="true"></span>
                            {{ __('beneficiaries.documents.upload_button') }}
                        </span>
                    </span>

                    <input id="document-upload" type="file" wire:model="upload" class="sr-only" />
                </label>

                @error('upload')
                    <p class="mt-1.5 text-xs text-status-rejected">{{ $message }}</p>
                @enderror
            </div>
        </div>
    @endcan

    <div class="space-y-6">
        @php $hasAnyDocument = false; @endphp

        @foreach (\App\Enums\DocumentType::cases() as $documentType)
            @php $media = $beneficiary->getMedia($documentType->value); @endphp

            @if ($media->isNotEmpty())
                @php $hasAnyDocument = true; @endphp

                <div>
                    <h3 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $documentType->label() }}</h3>

                    <ul class="divide-y divide-gray-100 rounded-(--radius-brand) border border-gray-100 dark:divide-white/10 dark:border-white/10">
                        @foreach ($media as $item)
                            <li wire:key="document-{{ $item->id }}" class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <svg class="h-5 w-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m5.231 13.481L15 17.25m-1.519-3.769a1 1 0 0 0-.363.363m1.882 3.406L11.7 14.5m0 0-2.2 2.2m2.2-2.2 2.2 2.2" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18.75V4.5a2.25 2.25 0 0 1 2.25-2.25h6.879a1.5 1.5 0 0 1 1.06.44l3.622 3.62a1.5 1.5 0 0 1 .44 1.061V18.75a2.25 2.25 0 0 1-2.25 2.25H8.25a2.25 2.25 0 0 1-2.25-2.25Z" />
                                    </svg>

                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $item->file_name }}</p>
                                        <p class="text-xs tabular-nums text-gray-500 dark:text-gray-400">
                                            {{ $item->human_readable_size }} &middot; {{ $item->created_at?->translatedFormat('Y/m/d') }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex shrink-0 items-center gap-1">
                                    <x-ui.button href="{{ route('admin.beneficiaries.documents.download', [$beneficiary, $item]) }}" target="_blank" variant="ghost" size="sm">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                        </svg>
                                        <span class="sr-only">{{ __('beneficiaries.documents.download') }}</span>
                                    </x-ui.button>

                                    @can('update', $beneficiary)
                                        <x-ui.button
                                            type="button"
                                            variant="danger"
                                            size="sm"
                                            wire:click="deleteDocument({{ $item->id }})"
                                            wire:confirm="{{ __('beneficiaries.documents.confirm_delete') }}"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                            <span class="sr-only">{{ __('common.delete') }}</span>
                                        </x-ui.button>
                                    @endcan
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach

        @if (! $hasAnyDocument)
            <x-ui.empty-state :title="__('beneficiaries.documents.empty_title')" :description="__('beneficiaries.documents.empty_description')" />
        @endif
    </div>
</div>
