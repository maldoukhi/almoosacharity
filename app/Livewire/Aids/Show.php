<?php

namespace App\Livewire\Aids;

use App\Actions\Confirmations\ResendConfirmationLink;
use App\Enums\AidStatus;
use App\Enums\ApprovalAction;
use App\Enums\RoleName;
use App\Enums\SurveyQuestionType;
use App\Exceptions\Confirmations\AidConfirmationException;
use App\Models\Aid;
use App\Models\AidConfirmation;
use App\Models\ApprovalDecision;
use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStage;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

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
     * The decisions recorded against this aid, newest first, in the shape
     * x-ui.timeline reads directly off an ApprovalDecision instance.
     *
     * @return Collection<int, ApprovalDecision>
     */
    #[Computed]
    public function timeline(): Collection
    {
        return $this->aid->decisions;
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
        return $this->aid->status === AidStatus::UnderReview
            && $this->aid->current_stage_id !== null
            && Gate::allows('act', $this->aid);
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
        ]);
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
            $this->allowedActions,
            $this->canSubmit,
            $this->canCancel,
            $this->latestReturnNote,
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
