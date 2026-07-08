<?php

namespace App\Livewire\Messaging;

use App\Actions\Messaging\SendBroadcast as SendBroadcastAction;
use App\Enums\MessageChannel;
use App\Models\Beneficiary;
use App\Models\BeneficiaryCategory;
use App\Models\MessageTemplate;
use App\Models\NotificationTemplate;
use App\Rules\SaudiMobile;
use App\Support\MobileNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Standalone bulk-messaging screen: filter beneficiaries (same filters as
 * the beneficiaries listing), pick/uncheck individual recipients, write
 * (or pick a saved template for) a free-text SMS/WhatsApp body with a
 * {name} placeholder, preview it, and send.
 *
 * Can also be reached from the beneficiaries listing's "send message"
 * quick action with a fixed set of ids in the `?ids=1,2,3` query string
 * (see {@see mount()}), in which case the initial selection is exactly
 * that set rather than "everything matching the current filters".
 */
class Broadcast extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $categoryFilter = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $cityFilter = '';

    public string $channel = 'sms';

    public string $body = '';

    /**
     * Selected template key: "msg:{id}" for a saved broadcast template or
     * "notif:{id}" for an active notification template reused as a body.
     */
    public ?string $selectedTemplate = null;

    public bool $saveAsTemplate = false;

    public string $newTemplateName = '';

    /** @var array<int, int> */
    public array $selectedIds = [];

    /**
     * Freely-typed numbers not tied to any beneficiary — one per line, or
     * separated by commas. See {@see parsedManualNumbers()} for how these
     * are normalized/validated before being counted or sent to.
     */
    public string $manualNumbers = '';

    /**
     * Whether the selection should always track "every beneficiary
     * currently matching the filters" (default) rather than a fixed,
     * manually-curated set.
     */
    public bool $selectAllFiltered = true;

    /**
     * Whether the "confirm before sending" modal (recipients + preview) is
     * open. Opened by {@see confirmSend()} once the message passes the same
     * validation {@see send()} enforces, so the modal never shows for an
     * invalid draft.
     */
    public bool $showSendConfirm = false;

    public function mount(): void
    {
        Gate::authorize('messages.broadcast');

        $ids = trim((string) request()->query('ids', ''));

        if ($ids !== '') {
            $requested = collect(explode(',', $ids))
                ->map(fn (string $id): int => (int) trim($id))
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values()
                ->all();

            // Only the ones that actually have a mobile number are kept
            // eligible, exactly like the rest of this screen.
            $this->selectedIds = Beneficiary::query()
                ->whereIn('id', $requested)
                ->whereNotNull('mobile')
                ->where('mobile', '!=', '')
                ->pluck('id')
                ->all();

            $this->selectAllFiltered = false;

            return;
        }

        $this->syncSelectionToFilters();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCityFilter(): void
    {
        $this->resetPage();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'categoryFilter', 'statusFilter', 'cityFilter'], true) && $this->selectAllFiltered) {
            $this->syncSelectionToFilters();
        }
    }

    public function updatedSelectAllFiltered(): void
    {
        $this->selectedIds = $this->selectAllFiltered ? $this->eligibleFilteredIds() : [];
    }

    public function updatedChannel(): void
    {
        $this->selectedTemplate = null;
    }

    public function toggleId(int $id): void
    {
        if (in_array($id, $this->selectedIds, true)) {
            $this->selectedIds = array_values(array_diff($this->selectedIds, [$id]));
        } else {
            $this->selectedIds[] = $id;
        }

        // A manual, individual toggle turns "select all filtered" off:
        // the selection is now a curated set, not "whatever the filters
        // match right now".
        $this->selectAllFiltered = false;
    }

    public function applyTemplate(): void
    {
        $body = $this->resolveTemplate($this->selectedTemplate)['body'] ?? null;

        if ($body !== null) {
            $this->body = $body;
        }
    }

    /**
     * Validate the draft and, if it is sendable, open the confirmation
     * modal. Runs the exact same checks {@see send()} does, so an invalid
     * draft surfaces its errors inline instead of opening the modal.
     */
    public function confirmSend(): void
    {
        Gate::authorize('messages.broadcast');

        if (! $this->passesPreflight()) {
            return;
        }

        $this->showSendConfirm = true;
    }

    public function cancelSend(): void
    {
        $this->showSendConfirm = false;
    }

    /**
     * Shared pre-send validation for {@see confirmSend()} and {@see send()}:
     * throws (Livewire renders the field errors) on a validation failure and
     * returns false, having added a specific error, on the business-rule
     * checks (invalid manual numbers, no/too many recipients).
     */
    private function passesPreflight(): bool
    {
        $maxLength = MessageChannel::from($this->channel) === MessageChannel::Sms ? 480 : 1000;

        $this->validate([
            'channel' => ['required', 'in:sms,whatsapp'],
            'body' => ['required', 'string', "max:{$maxLength}"],
            'newTemplateName' => ['required_if:saveAsTemplate,true', 'nullable', 'string', 'max:100'],
        ]);

        if ($this->manualNumbersInvalid !== []) {
            $this->addError('manualNumbers', __('messaging.broadcast.manual_numbers_invalid', [
                'numbers' => implode('، ', $this->manualNumbersInvalid),
            ]));

            return false;
        }

        if ($this->eligibleCount === 0) {
            $this->addError('body', __('messaging.broadcast.error_no_recipients'));

            return false;
        }

        // A hard recipient cap keeps a single broadcast (and its provider
        // cost) bounded; the confirm modal is client-side only, so this is
        // enforced server-side both here and in the action.
        if ($this->eligibleCount > SendBroadcastAction::MAX_RECIPIENTS) {
            $this->addError('body', __('messaging.broadcast.error_too_many_recipients', [
                'max' => SendBroadcastAction::MAX_RECIPIENTS,
            ]));

            return false;
        }

        return true;
    }

    public function send(): void
    {
        Gate::authorize('messages.broadcast');

        if (! $this->passesPreflight()) {
            $this->showSendConfirm = false;

            return;
        }

        // Server-side throttle against runaway/abusive sending: a handful of
        // broadcasts per minute per user is ample for legitimate use.
        $throttleKey = 'broadcast:'.Auth::id();

        if (RateLimiter::tooManyAttempts($throttleKey, maxAttempts: 5)) {
            $this->showSendConfirm = false;

            $this->addError('body', __('messaging.broadcast.error_throttled', [
                'seconds' => RateLimiter::availableIn($throttleKey),
            ]));

            return;
        }

        RateLimiter::hit($throttleKey, decaySeconds: 60);

        if ($this->saveAsTemplate) {
            MessageTemplate::create([
                'name' => trim($this->newTemplateName),
                'channel' => $this->channel,
                'body' => $this->body,
                'created_by' => Auth::id(),
            ]);
        }

        $broadcast = app(SendBroadcastAction::class)->handle(
            beneficiaryIds: $this->selectedIds,
            channel: $this->channel,
            body: $this->body,
            templateName: $this->resolveTemplate($this->selectedTemplate)['name'] ?? null,
            actor: Auth::user(),
            manualNumbers: $this->parsedManualNumbers(),
        );

        $this->dispatch('toast', type: 'success', message: __('messaging.broadcast.sent', ['count' => $broadcast->recipients_count]));

        $this->reset(['body', 'selectedTemplate', 'saveAsTemplate', 'newTemplateName', 'manualNumbers', 'showSendConfirm']);
    }

    /**
     * @return LengthAwarePaginator<int, Beneficiary>
     */
    #[Computed]
    public function recipients(): LengthAwarePaginator
    {
        return $this->filteredQuery(requireMobile: false)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15);
    }

    /**
     * @return Collection<int, BeneficiaryCategory>
     */
    #[Computed]
    public function categories(): Collection
    {
        return BeneficiaryCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    #[Computed]
    public function cities(): \Illuminate\Support\Collection
    {
        return Beneficiary::query()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');
    }

    /**
     * @return Collection<int, MessageTemplate>
     */
    #[Computed]
    public function templates(): Collection
    {
        return MessageTemplate::query()
            ->forChannel(MessageChannel::from($this->channel))
            ->orderBy('name')
            ->get();
    }

    /**
     * Active notification templates for the current channel, reusable as
     * broadcast bodies. Keyed "notif:{id}" to distinguish them from saved
     * broadcast templates in the picker.
     *
     * @return Collection<int, NotificationTemplate>
     */
    #[Computed]
    public function notificationTemplates(): Collection
    {
        return NotificationTemplate::query()
            ->where('channel', $this->channel)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Merged picker options (value => label): saved broadcast templates
     * plus active notification templates, so the ready-made messages
     * managed on the notification settings screen are usable here too.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function templateOptions(): array
    {
        $options = [];

        foreach ($this->templates as $template) {
            $options['msg:'.$template->id] = $template->name;
        }

        foreach ($this->notificationTemplates as $template) {
            $options['notif:'.$template->id] = __('messaging.broadcast.notification_template_label', [
                'event' => $template->event->label(),
            ]);
        }

        return $options;
    }

    /**
     * Resolve a "msg:{id}"/"notif:{id}" key to its body + display name.
     *
     * @return array{body: string, name: string}|null
     */
    private function resolveTemplate(?string $key): ?array
    {
        if ($key === null || ! str_contains($key, ':')) {
            return null;
        }

        [$source, $id] = explode(':', $key, 2);

        if ($source === 'msg') {
            $template = $this->templates->firstWhere('id', (int) $id);

            return $template ? ['body' => $template->body, 'name' => $template->name] : null;
        }

        if ($source === 'notif') {
            $template = $this->notificationTemplates->firstWhere('id', (int) $id);

            return $template ? [
                'body' => $template->body,
                'name' => __('messaging.broadcast.notification_template_label', ['event' => $template->event->label()]),
            ] : null;
        }

        return null;
    }

    /**
     * Total distinct recipients: selected beneficiaries with a mobile
     * number, unioned with the valid manual numbers, de-duplicated on
     * the normalized mobile format (a manual number that matches a
     * selected beneficiary's mobile is only counted once).
     */
    #[Computed]
    public function eligibleCount(): int
    {
        return count($this->eligibleNormalizedNumbers());
    }

    /**
     * How many *selected* beneficiaries actually have a mobile number
     * (ignores manual numbers) — used for the "X beneficiaries + Y
     * numbers = Z recipients" breakdown.
     */
    #[Computed]
    public function beneficiaryEligibleCount(): int
    {
        return Beneficiary::query()
            ->whereIn('id', $this->selectedIds)
            ->whereNotNull('mobile')
            ->where('mobile', '!=', '')
            ->count();
    }

    /**
     * How many beneficiaries match the current filters but have no
     * mobile number on file, and will therefore never receive this
     * broadcast even if "selected".
     */
    #[Computed]
    public function excludedNoMobileCount(): int
    {
        return $this->filteredQuery(requireMobile: false)
            ->where(function (Builder $query): void {
                $query->whereNull('mobile')->orWhere('mobile', '');
            })
            ->count();
    }

    /**
     * Valid, normalized, de-duplicated manual numbers (see
     * {@see parsedManualNumbers()}), exposed as a computed property so the
     * view can display a live "valid" count.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function manualNumbersValid(): array
    {
        return $this->parsedManualNumbers();
    }

    /**
     * Manually-typed numbers, normalized and de-duplicated, that did NOT
     * pass {@see SaudiMobile} validation — shown in the view as a specific
     * error and blocks {@see send()}.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function manualNumbersInvalid(): array
    {
        $valid = $this->parsedManualNumbers();

        return collect($this->manualNumberTokens())
            ->map(fn (string $token): string => MobileNumber::normalize($token))
            ->unique()
            ->reject(fn (string $number): bool => in_array($number, $valid, true))
            ->values()
            ->all();
    }

    #[Computed]
    public function previewBeneficiary(): ?Beneficiary
    {
        if ($this->selectedIds === []) {
            return null;
        }

        return Beneficiary::query()->whereKey($this->selectedIds[0])->first();
    }

    #[Computed]
    public function preview(): string
    {
        $name = $this->previewBeneficiary?->full_name ?? __('messaging.broadcast.preview_sample_name');

        return strtr($this->body, ['{name}' => $name]);
    }

    /**
     * @return array<int, int>
     */
    private function eligibleFilteredIds(): array
    {
        return $this->filteredQuery(requireMobile: true)->pluck('id')->all();
    }

    /**
     * Splits {@see $manualNumbers} into individual trimmed, non-blank
     * tokens — one per line, or comma-separated on the same line.
     *
     * @return array<int, string>
     */
    private function manualNumberTokens(): array
    {
        if (trim($this->manualNumbers) === '') {
            return [];
        }

        return collect(preg_split('/[\r\n,]+/', $this->manualNumbers) ?: [])
            ->map(fn (string $token): string => trim($token))
            ->filter(fn (string $token): bool => $token !== '')
            ->values()
            ->all();
    }

    /**
     * Every manually-typed number, normalized (see
     * {@see MobileNumber::normalize()}), de-duplicated, and filtered down
     * to the ones that pass {@see SaudiMobile} validation. The invalid
     * ones (still normalized/de-duplicated) are surfaced separately by
     * {@see manualNumbersInvalid()} so the view can show a specific error
     * rather than silently dropping them.
     *
     * @return array<int, string>
     */
    private function parsedManualNumbers(): array
    {
        $rule = new SaudiMobile;

        return collect($this->manualNumberTokens())
            ->map(fn (string $token): string => MobileNumber::normalize($token))
            ->unique()
            ->filter(function (string $number) use ($rule): bool {
                $isValid = true;

                $rule->validate('manualNumbers', $number, function () use (&$isValid): void {
                    $isValid = false;
                });

                return $isValid;
            })
            ->values()
            ->all();
    }

    /**
     * Distinct recipients across selected beneficiaries (with a mobile
     * number) and valid manual numbers, unioned on the normalized mobile
     * format so the same person/number is never counted (or sent to)
     * twice.
     *
     * @return array<int, string>
     */
    private function eligibleNormalizedNumbers(): array
    {
        $beneficiaryNumbers = Beneficiary::query()
            ->whereIn('id', $this->selectedIds)
            ->whereNotNull('mobile')
            ->where('mobile', '!=', '')
            ->pluck('mobile')
            ->map(fn (string $mobile): string => MobileNumber::normalize($mobile))
            ->all();

        return collect($beneficiaryNumbers)
            ->merge($this->parsedManualNumbers())
            ->unique()
            ->values()
            ->all();
    }

    private function syncSelectionToFilters(): void
    {
        $this->selectedIds = $this->eligibleFilteredIds();
    }

    /**
     * @return Builder<Beneficiary>
     */
    private function filteredQuery(bool $requireMobile): Builder
    {
        return Beneficiary::query()
            ->when($requireMobile, function (Builder $query): void {
                $query->whereNotNull('mobile')->where('mobile', '!=', '');
            })
            ->when($this->search !== '', function (Builder $query): void {
                $term = "%{$this->search}%";

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('first_name', 'like', $term)
                        ->orWhere('second_name', 'like', $term)
                        ->orWhere('third_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('national_id', 'like', $term)
                        ->orWhere('mobile', 'like', $term);
                });
            })
            ->when($this->categoryFilter !== '', function (Builder $query): void {
                $query->whereHas('categories', function (Builder $query): void {
                    $query->where('beneficiary_categories.id', $this->categoryFilter);
                });
            })
            ->when($this->statusFilter !== '', function (Builder $query): void {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->cityFilter !== '', function (Builder $query): void {
                $query->where('city', $this->cityFilter);
            });
    }

    public function render()
    {
        return view('livewire.messaging.broadcast');
    }
}
