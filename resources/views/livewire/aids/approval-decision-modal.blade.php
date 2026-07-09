@php
    $action = \App\Enums\ApprovalAction::tryFrom($this->action);
    $tone = match ($this->action) {
        'approve' => ['ring' => 'bg-status-approved/10 text-status-approved', 'box' => 'border-status-approved/20 bg-status-approved/5'],
        'reject' => ['ring' => 'bg-status-rejected/10 text-status-rejected', 'box' => 'border-status-rejected/20 bg-status-rejected/5'],
        default => ['ring' => 'bg-status-review/10 text-status-review', 'box' => 'border-status-review/20 bg-status-review/5'],
    };
@endphp

<div class="p-6">
    <div class="mb-5 flex items-start gap-3">
        <div class="flex size-10 shrink-0 items-center justify-center rounded-full {{ $tone['ring'] }}">
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                @if ($this->action === 'approve')
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                @elseif ($this->action === 'reject')
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                @else
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                @endif
            </svg>
        </div>
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ __('aids.decision_modal.title', ['action' => $this->actionLabel]) }}
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('aids.decision_modal.subtitle') }}
            </p>
        </div>
    </div>

    {{-- Summary --}}
    <dl class="divide-y divide-gray-100 rounded-(--radius-brand) border border-gray-100 dark:divide-white/10 dark:border-white/10">
        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('aids.field_reference') }}</dt>
            <dd class="text-sm font-medium tabular-nums text-gray-900 dark:text-white">{{ $aid->reference }}</dd>
        </div>
        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('aids.field_beneficiary') }}</dt>
            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $aid->beneficiary?->full_name }}</dd>
        </div>
        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('aids.field_current_stage') }}</dt>
            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $aid->currentStage?->name ?? '—' }}</dd>
        </div>
    </dl>

    <form wire:submit="confirm" class="mt-5 space-y-5">
        {{-- Note --}}
        <div>
            <label for="decisionModalNote" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
                {{ __('aids.field_decision_note') }}
                @if ($this->requiresNote)
                    <span class="text-status-rejected">*</span>
                @endif
            </label>
            <textarea
                id="decisionModalNote"
                wire:model="note"
                rows="3"
                class="block w-full rounded-(--radius-brand) border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm transition duration-200 ease-out focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-white/10 dark:bg-primary-950/30 dark:text-gray-100"
            ></textarea>
            @error('note')
                <p class="mt-1.5 text-xs text-status-rejected">{{ $message }}</p>
            @enderror
            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ __('aids.decision_note_hint') }}</p>
        </div>

        {{-- Supporting documents --}}
        @if (! empty($this->documentTypes))
            {{-- One labelled slot per admin-defined document type. --}}
            <div>
                <p class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
                    {{ __('approvals.decision.documents_label') }}
                </p>

                <div class="space-y-3">
                    @foreach ($this->documentTypes as $i => $type)
                        <div wire:key="typed-doc-{{ $i }}" class="rounded-(--radius-brand) border border-gray-200 p-3 dark:border-white/10">
                            <div class="mb-1.5 flex items-center justify-between gap-2">
                                <span class="text-sm font-medium text-gray-800 dark:text-gray-100">
                                    {{ $type['label'] }}
                                    @if ($type['required'])
                                        <span class="text-status-rejected">*</span>
                                    @endif
                                </span>

                                <span @class([
                                    'rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-status-rejected/10 text-status-rejected' => $type['required'],
                                    'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400' => ! $type['required'],
                                ])>
                                    {{ $type['required'] ? __('approvals.decision.mandatory') : __('approvals.decision.optional') }}
                                </span>
                            </div>

                            <input
                                id="typedDocument{{ $i }}"
                                type="file"
                                wire:model="typedDocuments.{{ $i }}"
                                accept=".pdf,.jpg,.jpeg,.png"
                                class="block w-full cursor-pointer rounded-(--radius-brand) border border-gray-300 bg-white text-sm text-gray-900 shadow-sm transition duration-200 ease-out file:me-3 file:border-0 file:bg-gray-50 file:px-3.5 file:py-2.5 file:text-sm file:font-medium file:text-primary-700 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-white/10 dark:bg-primary-950/30 dark:text-gray-100 dark:file:bg-white/5 dark:file:text-primary-200"
                            />

                            <div wire:loading wire:target="typedDocuments.{{ $i }}" class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                                {{ __('approvals.decision.uploading') }}
                            </div>

                            @error("typedDocuments.{$i}")
                                <p class="mt-1.5 text-xs text-status-rejected">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>

                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('approvals.decision.typed_documents_hint') }}
                </p>
            </div>
        @else
            {{-- No named types: one anonymous multi-file field (required or optional). --}}
            <div>
                <label for="decisionModalDocuments" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
                    {{ __('approvals.decision.documents_label') }}
                    @if ($this->documentsRequired)
                        <span class="text-status-rejected">*</span>
                    @endif
                </label>

                <input
                    id="decisionModalDocuments"
                    type="file"
                    wire:model="documents"
                    multiple
                    accept=".pdf,.jpg,.jpeg,.png"
                    class="block w-full cursor-pointer rounded-(--radius-brand) border border-gray-300 bg-white text-sm text-gray-900 shadow-sm transition duration-200 ease-out file:me-3 file:border-0 file:bg-gray-50 file:px-3.5 file:py-2.5 file:text-sm file:font-medium file:text-primary-700 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-white/10 dark:bg-primary-950/30 dark:text-gray-100 dark:file:bg-white/5 dark:file:text-primary-200"
                />

                <div wire:loading wire:target="documents" class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('approvals.decision.uploading') }}
                </div>

                @error('documents')
                    <p class="mt-1.5 text-xs text-status-rejected">{{ $message }}</p>
                @enderror
                @error('documents.*')
                    <p class="mt-1.5 text-xs text-status-rejected">{{ $message }}</p>
                @enderror

                <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                    {{ $this->documentsRequired ? __('approvals.decision.documents_hint_required') : __('approvals.decision.documents_hint_optional') }}
                </p>
            </div>
        @endif

        {{-- What happens: notification preview --}}
        <div class="rounded-(--radius-brand) border {{ $tone['box'] }} p-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                {{ __('aids.decision_modal.effect_title') }}
            </h3>

            @if ($action === \App\Enums\ApprovalAction::Approve)
                @if ($this->isFinalApprove)
                    <p class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-200">
                        {{ __('aids.decision_modal.approve_final') }}
                    </p>
                @else
                    <p class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-200">
                        {{ __('aids.decision_modal.approve_next', ['stage' => $this->nextStage->name, 'role' => $this->nextReviewerRoleLabel]) }}
                    </p>
                    @if ($this->nextRecipientCount > 0)
                        <p class="mt-1 text-sm leading-6 text-gray-700 dark:text-gray-200">
                            {{ trans_choice('aids.submit_modal.notify_recipients', $this->nextRecipientCount, ['count' => $this->nextRecipientCount]) }}
                        </p>
                    @else
                        <p class="mt-1 text-sm leading-6 text-status-review">{{ __('aids.submit_modal.notify_none') }}</p>
                    @endif
                @endif
            @elseif ($action === \App\Enums\ApprovalAction::Reject)
                <p class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-200">
                    {{ __('aids.decision_modal.reject_effect') }}
                </p>
            @else
                <p class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-200">
                    {{ __('aids.decision_modal.return_effect') }}
                </p>
            @endif
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5 dark:border-white/10">
            <x-ui.button type="button" variant="ghost" wire:click="closeModal">
                {{ __('common.cancel') }}
            </x-ui.button>

            <x-ui.button
                type="submit"
                variant="{{ $this->action === 'reject' ? 'danger' : ($this->action === 'approve' ? 'secondary' : 'primary') }}"
                wire:target="confirm"
            >
                {{ $this->actionLabel }}
            </x-ui.button>
        </div>
    </form>
</div>
