<?php

namespace App\Livewire\Aids;

use App\Actions\Approvals\RequestBeneficiaryStageResponse;
use App\Actions\Confirmations\ResendConfirmationLink;
use App\Enums\AidStatus;
use App\Enums\ApprovalAction;
use App\Enums\RoleName;
use App\Enums\SurveyQuestionType;
use App\Exceptions\Confirmations\AidConfirmationException;
use App\Models\Aid;
use App\Models\AidConfirmation;
use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStage;
use App\Models\BeneficiaryStageResponse;
use App\Models\Disbursement;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Support\ArabicPdf;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Aid detail screen: timeline, decisions, items and the approval action
 * buttons. Submit, approve/reject/return and cancel each open their own
 * confirmation modal (SubmitAidModal / ApprovalDecisionModal /
 * CancelAidModal), which dispatch `aid-submitted` back here to refresh.
 */
class Show extends Component
{
    public Aid $aid;

    public function mount(Aid $aid): void
    {
        $this->aid = $aid;

        Gate::authorize('view', $this->aid);

        $this->eagerLoad();
    }

    /**
     * The ordered stages of the flow that applies to this aid — its own
     * submitted-flow snapshot once it exists, otherwise the flow the
     * assigned program (or the default active flow) would use once
     * submitted, so the timeline can be previewed before submission —
     * shaped for x-ui.stepper (label/meta pairs).
     *
     * @return Collection<int, array{label: string, meta: ?string}>
     */
    #[Computed]
    public function stages(): Collection
    {
        return $this->resolveStages()->map(fn (ApprovalFlowStage $stage): array => [
            'label' => $stage->name,
            'meta' => RoleName::tryFrom($stage->role)?->label() ?? $stage->role,
        ]);
    }

    /**
     * Zero-based position of the aid's current stage within {@see stages},
     * or null if the aid has no current stage (not yet submitted, or the
     * workflow has already concluded).
     */
    #[Computed]
    public function currentStageIndex(): ?int
    {
        if (! $this->aid->current_stage_id) {
            return null;
        }

        $index = $this->resolveStages()->search(
            fn (ApprovalFlowStage $stage): bool => $stage->id === $this->aid->current_stage_id,
        );

        return $index === false ? null : $index;
    }

    /**
     * A single chronological timeline merging every event recorded against
     * this aid: creation, submission, each approval decision, the
     * disbursement lifecycle (started/delivered/second-check confirmed)
     * and the beneficiary confirmation link (sent/confirmed) — sorted
     * ascending (oldest first) so it reads as one continuous story.
     *
     * Each entry is shaped ['at' => Carbon, 'title' => string,
     * 'meta' => ?string, 'color' => string, 'icon' => string], ready to be
     * mapped onto x-ui.timeline's item shape in the view. Relations that
     * don't exist yet (no disbursement, no confirmation, no decisions) are
     * defensively skipped rather than causing an error.
     *
     * @return array<int, array{at: ?Carbon, title: string, meta: ?string, color: string, icon: string}>
     */
    #[Computed]
    public function timeline(): array
    {
        $entries = collect();

        $entries->push([
            'at' => $this->aid->created_at,
            'title' => __('aids.timeline.created'),
            'meta' => $this->aid->createdBy?->name,
            'color' => 'primary',
            'icon' => 'dot',
        ]);

        if ($this->aid->submitted_at) {
            $entries->push([
                'at' => $this->aid->submitted_at,
                'title' => __('aids.timeline.submitted'),
                'meta' => null,
                'color' => 'review',
                'icon' => 'dot',
            ]);
        }

        foreach ($this->aid->decisions as $decision) {
            if (! $decision->decided_at) {
                continue;
            }

            $entries->push([
                'at' => $decision->decided_at,
                'title' => trim(($decision->action?->label() ?? '').' — '.$decision->stage_name),
                'meta' => collect([$decision->user?->name, $decision->note])->filter()->implode(' · ') ?: null,
                'color' => match ($decision->action) {
                    ApprovalAction::Approve => 'approved',
                    ApprovalAction::Reject => 'rejected',
                    ApprovalAction::Return => 'review',
                    default => 'primary',
                },
                'icon' => match ($decision->action) {
                    ApprovalAction::Approve => 'check',
                    ApprovalAction::Reject => 'x',
                    ApprovalAction::Return => 'undo',
                    default => 'dot',
                },
            ]);
        }

        if ($disbursement = $this->aid->disbursement) {
            $this->pushDisbursementEntries($entries, $disbursement);
        }

        if ($confirmation = $this->aid->confirmation) {
            $this->pushConfirmationEntries($entries, $confirmation);
        }

        return $entries
            ->filter(fn (array $entry): bool => $entry['at'] !== null)
            ->sortBy('at')
            ->values()
            ->all();
    }

    /**
     * Append the disbursement's own lifecycle events (started, delivered,
     * second-check confirmed) to the timeline entries collection, skipping
     * any that haven't happened yet.
     */
    private function pushDisbursementEntries(Collection $entries, Disbursement $disbursement): void
    {
        if ($disbursement->started_at) {
            $entries->push([
                'at' => $disbursement->started_at,
                'title' => __('aids.timeline.disbursement_started', ['method' => $disbursement->method->label()]),
                'meta' => $disbursement->startedBy?->name,
                'color' => 'review',
                'icon' => 'dot',
            ]);
        }

        if ($disbursement->delivered_at) {
            $entries->push([
                'at' => $disbursement->delivered_at,
                'title' => __('aids.timeline.disbursement_delivered'),
                'meta' => $disbursement->deliveredBy?->name,
                'color' => 'approved',
                'icon' => 'check',
            ]);
        }

        if ($disbursement->confirmed_at) {
            $entries->push([
                'at' => $disbursement->confirmed_at,
                'title' => __('aids.timeline.disbursement_confirmed'),
                'meta' => $disbursement->confirmedBy?->name,
                'color' => 'approved',
                'icon' => 'check',
            ]);
        }
    }

    /**
     * Append the beneficiary confirmation link's own events (link sent,
     * beneficiary confirmed with its receipt status) to the timeline
     * entries collection, skipping any that haven't happened yet.
     */
    private function pushConfirmationEntries(Collection $entries, AidConfirmation $confirmation): void
    {
        if ($confirmation->sent_at) {
            $entries->push([
                'at' => $confirmation->sent_at,
                'title' => __('aids.timeline.confirmation_sent'),
                'meta' => null,
                'color' => 'primary',
                'icon' => 'dot',
            ]);
        }

        if ($confirmation->confirmed_at) {
            $entries->push([
                'at' => $confirmation->confirmed_at,
                'title' => __('aids.timeline.confirmation_confirmed'),
                'meta' => $confirmation->receipt_status?->label(),
                'color' => $confirmation->receipt_status?->color() ?? 'approved',
                'icon' => 'check',
            ]);
        }
    }

    /**
     * The raw, ordered stage models behind {@see stages()} and
     * {@see currentStageIndex()} — kept separate since the latter needs
     * each stage's id, which the label/meta shape above discards.
     *
     * @return Collection<int, ApprovalFlowStage>
     */
    private function resolveStages(): Collection
    {
        $flow = $this->aid->approvalFlow
            ?? $this->aid->program?->approvalFlow
            ?? ApprovalFlow::query()->default()->where('is_active', true)->first();

        return $flow?->stages ?? collect();
    }

    #[Computed]
    public function canAct(): bool
    {
        // The status guard matters for a system-admin, whose Gate::before
        // short-circuits the policy's own state checks: without it every
        // action button would show regardless of the aid's actual state.
        //
        // A beneficiary_response stage is deliberately excluded: it waits on
        // the beneficiary, so staff get the awaiting-beneficiary state (with
        // a resend action) rather than approve/reject buttons.
        return $this->aid->status === AidStatus::UnderReview
            && $this->aid->current_stage_id !== null
            && ! (bool) $this->aid->currentStage?->isBeneficiaryResponse()
            && Gate::allows('act', $this->aid);
    }

    /**
     * True when the aid is parked at a beneficiary_response stage and the
     * signed-in user is eligible for that stage: they see that the aid is
     * waiting on the beneficiary, plus a "resend link" action, instead of
     * approve/reject controls.
     */
    #[Computed]
    public function awaitingBeneficiary(): bool
    {
        return $this->aid->status === AidStatus::UnderReview
            && (bool) $this->aid->currentStage?->isBeneficiaryResponse()
            && Gate::allows('act', $this->aid);
    }

    /**
     * The beneficiary stage-response link record for the aid's current
     * beneficiary_response stage, if any — used to surface send/open
     * tracking beside the resend action. Null otherwise.
     */
    #[Computed]
    public function stageResponse(): ?BeneficiaryStageResponse
    {
        if (! $this->aid->currentStage?->isBeneficiaryResponse()) {
            return null;
        }

        return BeneficiaryStageResponse::query()
            ->where('aid_id', $this->aid->id)
            ->where('approval_flow_stage_id', $this->aid->current_stage_id)
            ->first();
    }

    /**
     * The approval actions allowed at the aid's current stage.
     *
     * @return array<int, ApprovalAction>
     */
    #[Computed]
    public function allowedActions(): array
    {
        $stage = $this->aid->currentStage;

        if (! $stage) {
            return [];
        }

        return collect(ApprovalAction::cases())
            ->filter(fn (ApprovalAction $action): bool => $stage->allows($action))
            ->values()
            ->all();
    }

    #[Computed]
    public function canSubmit(): bool
    {
        // Draft-only: without this a system-admin (Gate::before) would see
        // the "submit" button on an already-submitted aid.
        return $this->aid->status === AidStatus::Draft
            && Gate::allows('submit', $this->aid);
    }

    #[Computed]
    public function canCancel(): bool
    {
        return in_array($this->aid->status, [AidStatus::Draft, AidStatus::Submitted, AidStatus::UnderReview], true)
            && Gate::allows('cancel', $this->aid);
    }

    /**
     * The PDF receipt ("سند صرف إعانة") only makes sense once the aid has
     * actually been approved — before that there is nothing to hand the
     * beneficiary a voucher for, and a rejected/cancelled aid was never
     * disbursed at all.
     */
    #[Computed]
    public function canDownloadReceipt(): bool
    {
        return in_array($this->aid->status, [
            AidStatus::Approved,
            AidStatus::InDisbursement,
            AidStatus::Delivered,
            AidStatus::Confirmed,
        ], true) && Gate::allows('view', $this->aid);
    }

    /**
     * The note from the most recent "return" decision, surfaced as a
     * heads-up banner while the aid is back in draft awaiting rework.
     */
    #[Computed]
    public function latestReturnNote(): ?string
    {
        if ($this->aid->status !== AidStatus::Draft) {
            return null;
        }

        return $this->aid->decisions
            ->firstWhere('action', ApprovalAction::Return)
            ?->note;
    }

    /**
     * The 1-based position of this aid within its recurring series (ordered
     * by creation), or null when the aid is not a generated series instance.
     * Counts the series aids raised on or before this one — cheap enough to
     * surface the cycle number on the detail card.
     */
    #[Computed]
    public function recurringCycle(): ?int
    {
        if (! $this->aid->isRecurringInstance()) {
            return null;
        }

        return Aid::query()
            ->where('recurring_aid_plan_id', $this->aid->recurring_aid_plan_id)
            ->where('created_at', '<=', $this->aid->created_at)
            ->count();
    }

    /**
     * The aid's single delivery-confirmation-link record, once its
     * disbursement has been recorded as delivered (phase 6b) — null
     * before that point.
     */
    #[Computed]
    public function confirmation(): ?AidConfirmation
    {
        return $this->aid->confirmation;
    }

    #[Computed]
    public function canResendConfirmation(): bool
    {
        return $this->confirmation !== null && Gate::allows('resend', $this->confirmation);
    }

    /**
     * The single survey response the beneficiary submitted for THIS aid,
     * resolved strictly through the aid's own confirmation link (never a
     * global survey query) so the detail below can only ever surface an
     * answer set that genuinely belongs to this aid. Works identically
     * whether the answered survey is program-scoped or general, since the
     * response itself already carries its survey_id. Null until a response
     * exists.
     */
    #[Computed]
    public function surveyResponse(): ?SurveyResponse
    {
        $confirmation = $this->aid->confirmation;

        if (! $confirmation) {
            return null;
        }

        return $confirmation->surveyResponses()
            ->with(['survey.questions', 'answers'])
            ->latest('submitted_at')
            ->first();
    }

    /**
     * Whether the survey-results card should be shown at all: either an
     * answer set exists, or the aid has reached a delivery state where the
     * (still-empty) survey card's empty state is meaningful.
     */
    #[Computed]
    public function showsSurveyCard(): bool
    {
        return $this->surveyResponse !== null
            || in_array($this->aid->status, [AidStatus::Delivered, AidStatus::Confirmed], true);
    }

    /**
     * This aid's own survey answers — one entry per question in order, each
     * carrying the beneficiary's actual answer shaped for its type (this is
     * the per-aid detail, not the aggregate percentages on the survey
     * results screen). Empty when no response exists yet.
     *
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function surveyDetail(): Collection
    {
        $response = $this->surveyResponse;

        if (! $response || ! $response->survey) {
            return collect();
        }

        $answers = $response->answers->keyBy('survey_question_id');

        return $response->survey->questions->map(function (SurveyQuestion $question) use ($answers): array {
            $value = $answers->get($question->id)?->value;
            $answered = $value !== null && $value !== [];

            $base = [
                'id' => $question->id,
                'label' => $question->label,
                'type_label' => $question->type->label(),
                'answered' => $answered,
            ];

            return match ($question->type) {
                SurveyQuestionType::SingleChoice,
                SurveyQuestionType::MultipleChoice => $base + [
                    'kind' => 'choice',
                    'labels' => $this->choiceLabels($question, $value),
                ],
                SurveyQuestionType::Rating => $base + [
                    'kind' => 'rating',
                    'rating' => $answered ? (int) $value : null,
                    'max_stars' => (int) ($question->config['max_stars'] ?? 5),
                ],
                SurveyQuestionType::YesNo => $base + [
                    'kind' => 'yes_no',
                    'yes' => $answered ? (bool) $value : null,
                ],
                default => $base + [
                    'kind' => 'text',
                    'text' => $answered ? (string) $value : null,
                ],
            };
        });
    }

    /**
     * Map a choice question's stored value(s) to their human labels,
     * falling back to the raw value when an option was later removed.
     *
     * @param  mixed  $value
     * @return array<int, string>
     */
    private function choiceLabels(SurveyQuestion $question, $value): array
    {
        $values = is_array($value) ? $value : ($value === null ? [] : [$value]);
        $options = collect($question->options ?? [])->keyBy('value');

        return collect($values)
            ->map(fn ($item): string => $options->get($item)['label'] ?? (string) $item)
            ->all();
    }

    public function resendConfirmation(): void
    {
        $confirmation = $this->confirmation;

        if (! $confirmation) {
            abort(404);
        }

        Gate::authorize('resend', $confirmation);

        try {
            app(ResendConfirmationLink::class)->handle($confirmation, Auth::user());
        } catch (AidConfirmationException|AuthorizationException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());

            return;
        }

        $this->dispatch('toast', type: 'success', message: __('confirmations.messages.resent'));

        $this->refreshAid();
    }

    /**
     * Re-issue the beneficiary response link for the aid's current
     * beneficiary_response stage. Gated on the same `act` ability as an
     * approval decision, so only staff eligible for the stage can resend.
     */
    public function resendBeneficiaryLink(): void
    {
        $stage = $this->aid->currentStage;

        if (! $stage?->isBeneficiaryResponse() || $this->aid->status !== AidStatus::UnderReview) {
            abort(404);
        }

        Gate::authorize('act', $this->aid);

        app(RequestBeneficiaryStageResponse::class)->handle($this->aid, $stage);

        $this->dispatch('toast', type: 'success', message: __('approvals.beneficiary_response.link_resent'));

        $this->refreshAid();
    }

    private function eagerLoad(): void
    {
        $this->aid->load([
            'beneficiary',
            'program.approvalFlow.stages',
            'items',
            'currentStage.approvalFlow',
            'decisions.user',
            'createdBy',
            'approvalFlow.stages',
            'confirmation',
            'recurringPlan',
            'recurringPlanSeries',
            'disbursement.startedBy',
            'disbursement.deliveredBy',
            'disbursement.confirmedBy',
        ]);
    }

    /**
     * Stream the "سند صرف إعانة" (aid receipt) PDF for this aid — same
     * 'view' authorization as the page itself, so anyone who can see the
     * aid can print its receipt. Returning a streamDownload response from
     * a Livewire action triggers the browser download automatically (same
     * pattern as App\Livewire\Reports\AidsReport::exportPdf()).
     */
    public function downloadReceipt(): StreamedResponse
    {
        Gate::authorize('view', $this->aid);

        $aid = $this->aid;

        // ArabicPdf shapes the Arabic runs before dompdf renders them —
        // dompdf alone outputs Arabic disconnected/misordered.
        $pdf = ArabicPdf::loadView('pdf.aid-receipt', [
            'aid' => $aid,
            'maskedNationalId' => $this->maskNationalId($aid->beneficiary?->national_id),
        ]);

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            'receipt-'.$aid->reference.'.pdf',
        );
    }

    /**
     * Partial mask matching the pattern already used for national IDs in
     * reports (App\Reports\BeneficiariesReport): keep the first and last
     * two digits only, never the full identifier.
     */
    private function maskNationalId(?string $nationalId): ?string
    {
        if (! $nationalId) {
            return null;
        }

        return substr($nationalId, 0, 2).'••••••'.substr($nationalId, -2);
    }

    /**
     * Also listens for 'disbursement-updated', dispatched by the nested
     * Disbursements\Panel component whenever it starts/records/confirms a
     * disbursement, and 'aid-submitted', dispatched by SubmitAidModal on a
     * successful submission — both change this aid's own status.
     */
    #[On('disbursement-updated')]
    #[On('aid-submitted')]
    public function refreshAid(): void
    {
        $this->aid->refresh();

        $this->eagerLoad();

        unset(
            $this->stages,
            $this->currentStageIndex,
            $this->timeline,
            $this->canAct,
            $this->awaitingBeneficiary,
            $this->stageResponse,
            $this->allowedActions,
            $this->canSubmit,
            $this->canCancel,
            $this->canDownloadReceipt,
            $this->latestReturnNote,
            $this->recurringCycle,
            $this->confirmation,
            $this->canResendConfirmation,
            $this->surveyResponse,
            $this->showsSurveyCard,
            $this->surveyDetail,
        );
    }

    public function render()
    {
        return view('livewire.aids.show');
    }
}
