@php
    $documentTypeOptions = collect(\App\Enums\DocumentType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]);

    // A small distinct accent per document type keeps the grouped cards easy
    // to tell apart at a glance while staying within the brand token palette.
    $typeAccents = [
        'national_id_doc' => 'primary',
        'property_deed' => 'accent',
        'rent_contract' => 'accent',
        'medical_report' => 'secondary',
        'income_proof' => 'primary',
        'other' => 'gray',
    ];
@endphp

<div class="space-y-6">
    <div>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('beneficiaries.documents.title') }}</h2>
    </div>

    @can('update', $beneficiary)
        <div class="rounded-(--radius-brand) border border-gray-200 bg-gray-50/60 p-4 dark:border-white/10 dark:bg-white/[0.03]">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-ui.select
                    :label="__('beneficiaries.documents.field_type')"
                    name="documentType"
                    wire:model="documentType"
                    :placeholder="__('beneficiaries.select_placeholder')"
                    :options="$documentTypeOptions"
                />

                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
                        {{ __('beneficiaries.documents.upload_label') }}
                    </label>

                    <label
                        for="document-upload"
                        wire:loading.class="pointer-events-none opacity-70"
                        wire:target="upload"
                        class="group flex cursor-pointer items-center justify-center gap-3 rounded-(--radius-brand) border-2 border-dashed border-gray-300 bg-white px-4 py-4 text-center transition duration-150 ease-out hover:border-primary-400 hover:bg-primary-50/40 dark:border-white/10 dark:bg-primary-950/20 dark:hover:border-primary-600"
                    >
                        <span wire:loading.remove wire:target="upload" class="contents">
                            <svg class="h-6 w-6 shrink-0 text-gray-400 transition duration-150 group-hover:text-primary-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3.75 3.75 0 0 1 4.132 6.331A5.25 5.25 0 0 1 17.25 19.5H6.75Z" />
                            </svg>
                        </span>

                        <span wire:loading wire:target="upload" class="h-6 w-6 shrink-0 animate-spin rounded-full border-2 border-primary-400 border-t-transparent motion-reduce:animate-none" aria-hidden="true"></span>

                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            <span wire:loading.remove wire:target="upload">{{ __('beneficiaries.documents.upload_hint') }}</span>
                            <span wire:loading wire:target="upload" class="text-primary-600 dark:text-primary-300">{{ __('beneficiaries.documents.upload_button') }}&hellip;</span>
                        </span>

                        <input id="document-upload" type="file" wire:model="upload" class="sr-only" accept=".pdf,.jpg,.jpeg,.png" />
                    </label>

                    @error('upload')
                        <p class="mt-1.5 text-xs text-status-rejected">{{ $message }}</p>
                    @enderror

                    @error('documentType')
                        <p class="mt-1.5 text-xs text-status-rejected">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>
    @endcan

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        @foreach (\App\Enums\DocumentType::cases() as $documentType)
            @php
                $media = $this->documents[$documentType->value] ?? collect();
                $accent = $typeAccents[$documentType->value] ?? 'gray';
            @endphp

            <div
                wire:key="document-group-{{ $documentType->value }}"
                class="rounded-(--radius-brand) border border-gray-100 bg-white dark:border-white/10 dark:bg-primary-950/10"
            >
                <div class="flex items-center justify-between gap-2 border-b border-gray-100 px-4 py-3 dark:border-white/10">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <span
                            @class([
                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-full',
                                'bg-primary-100 text-primary-700 dark:bg-primary-500/20 dark:text-primary-200' => $accent === 'primary',
                                'bg-accent-100 text-accent-700 dark:bg-accent-500/20 dark:text-accent-200' => $accent === 'accent',
                                'bg-secondary-100 text-secondary-700 dark:bg-secondary-500/20 dark:text-secondary-200' => $accent === 'secondary',
                                'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300' => $accent === 'gray',
                            ])
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m5.231 13.481L15 17.25m-1.519-3.769a1 1 0 0 0-.363.363m1.882 3.406L11.7 14.5m0 0-2.2 2.2m2.2-2.2 2.2 2.2" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18.75V4.5a2.25 2.25 0 0 1 2.25-2.25h6.879a1.5 1.5 0 0 1 1.06.44l3.622 3.62a1.5 1.5 0 0 1 .44 1.061V18.75a2.25 2.25 0 0 1-2.25 2.25H8.25a2.25 2.25 0 0 1-2.25-2.25Z" />
                            </svg>
                        </span>

                        <h3 class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $documentType->label() }}</h3>
                    </div>

                    <x-ui.badge :color="$media->isNotEmpty() ? 'primary' : 'gray'">
                        {{ trans_choice('beneficiaries.documents.file_count', $media->count(), ['count' => $media->count()]) }}
                    </x-ui.badge>
                </div>

                <div class="p-3">
                    @forelse ($media as $item)
                        @php $preview = $this->thumbnail($item); @endphp

                        <div
                            wire:key="document-{{ $item->id }}"
                            class="flex items-center justify-between gap-3 rounded-(--radius-brand) px-2 py-2 transition duration-150 ease-out hover:bg-gray-50 dark:hover:bg-white/5"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                @if ($preview)
                                    <img src="{{ $preview }}" alt="" class="h-11 w-11 shrink-0 rounded-md object-cover ring-1 ring-gray-200 dark:ring-white/10" loading="lazy" />
                                @else
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md bg-gray-50 text-gray-400 ring-1 ring-gray-200 dark:bg-white/5 dark:text-gray-500 dark:ring-white/10">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m5.231 13.481L15 17.25m-1.519-3.769a1 1 0 0 0-.363.363m1.882 3.406L11.7 14.5m0 0-2.2 2.2m2.2-2.2 2.2 2.2" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18.75V4.5a2.25 2.25 0 0 1 2.25-2.25h6.879a1.5 1.5 0 0 1 1.06.44l3.622 3.62a1.5 1.5 0 0 1 .44 1.061V18.75a2.25 2.25 0 0 1-2.25 2.25H8.25a2.25 2.25 0 0 1-2.25-2.25Z" />
                                        </svg>
                                    </span>
                                @endif

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
                        </div>
                    @empty
                        <p class="px-2 py-3 text-center text-xs text-gray-400 dark:text-gray-500">{{ __('beneficiaries.documents.empty_description') }}</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>
