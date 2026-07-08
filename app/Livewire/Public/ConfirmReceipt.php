<?php

namespace App\Livewire\Public;

use App\Actions\Confirmations\ConfirmAidReceipt;
use App\Actions\Surveys\RecordSurveyResponse;
use App\Actions\Surveys\ResolveSurveyForAid;
use App\Listeners\CreateConfirmationOnDelivery;
use App\Models\Aid;
use App\Models\AidConfirmation;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The public, unauthenticated "confirm receipt" page reached only via the
 * signed link sent when an aid's disbursement is delivered (see
 * {@see CreateConfirmationOnDelivery}). No sensitive data
 * (cash amount, IBAN, national id...) is ever exposed here — only the
 * non-sensitive delivery summary needed for the beneficiary to recognise
 * what they're confirming.
 *
 * A genuinely time-expired link never reaches this component at all: the
 * route's own 'signed' middleware rejects it first (see the
 * InvalidSignatureException render registered in bootstrap/app.php,
 * which shows the same friendly "expired" copy from a plain view
 * instead). The `expired` state below only exists as a second line of
 * defense for the rare case the model's own expires_at and the
 * signature's embedded expiry could ever disagree.
 */
#[Layout('layouts::public')]
class ConfirmReceipt extends Component
{
    #[Locked]
    public AidConfirmation $confirmation;

    /**
     * confirm | already | expired | success | survey | done
     */
    #[Locked]
    public string $view = 'confirm';

    /**
     * Answers keyed by survey_question_id.
     *
     * @var array<int, mixed>
     */
    public array $answers = [];

    public int $step = 0;

    public function mount(AidConfirmation $confirmation): void
    {
        $rawToken = (string) request()->query('token', '');

        if ($rawToken === '' || ! hash_equals($confirmation->token_hash, AidConfirmation::hashToken($rawToken))) {
            $this->logAccessFailure($confirmation, 'invalid or missing token');

            abort(403);
        }

        $this->confirmation = $confirmation;

        if ($confirmation->isConfirmed()) {
            $this->view = 'already';

            return;
        }

        if ($confirmation->isExpired()) {
            $this->view = 'expired';

            return;
        }

        if ($confirmation->opened_at === null) {
            $confirmation->update(['opened_at' => now()]);

            activity('aid-confirmation')
                ->performedOn($confirmation)
                ->log('confirmation link opened');
        }

        $this->view = 'confirm';
    }

    #[Computed]
    public function aid(): Aid
    {
        return $this->confirmation->aid()->with(['beneficiary', 'program', 'items', 'disbursement'])->firstOrFail();
    }

    #[Computed]
    public function survey(): ?Survey
    {
        return app(ResolveSurveyForAid::class)->handle($this->aid);
    }

    /**
     * @return Collection<int, SurveyQuestion>
     */
    #[Computed]
    public function questions(): Collection
    {
        return $this->survey?->questions ?? collect();
    }

    #[Computed]
    public function currentQuestion(): ?SurveyQuestion
    {
        return $this->questions->values()->get($this->step);
    }

    #[Computed]
    public function totalSteps(): int
    {
        return $this->questions->count();
    }

    public function confirm(): void
    {
        if ($this->view !== 'confirm') {
            return;
        }

        app(ConfirmAidReceipt::class)->handle(
            $this->confirmation,
            (string) (request()->ip() ?? '0.0.0.0'),
            (string) request()->userAgent(),
        );

        $this->confirmation->refresh();

        $this->view = 'success';
    }

    public function startSurvey(): void
    {
        if ($this->view !== 'success' || ! $this->survey) {
            return;
        }

        $this->step = 0;
        $this->answers = [];
        $this->view = 'survey';
    }

    public function nextStep(): void
    {
        if ($this->view !== 'survey') {
            return;
        }

        $question = $this->currentQuestion;

        if (! $question) {
            $this->submitSurvey();

            return;
        }

        $this->validateCurrentAnswer($question);

        if ($this->step + 1 >= $this->totalSteps) {
            $this->submitSurvey();

            return;
        }

        $this->step++;

        // currentQuestion is a #[Computed] and was already read (and thus
        // memoized for this request) above, before step changed. Without
        // clearing that cache the re-render would show the *previous*
        // question and the screen would look stuck on the same step.
        unset($this->currentQuestion);
    }

    public function previousStep(): void
    {
        if ($this->view !== 'survey' || $this->step <= 0) {
            return;
        }

        $this->step--;

        unset($this->currentQuestion);
    }

    /**
     * Sets a single question's answer from a discrete-choice widget
     * (single choice, yes/no, rating stars) — used instead of wire:model
     * so the click itself both records the value and re-renders the
     * selected state immediately.
     */
    public function selectAnswer(int $questionId, mixed $value): void
    {
        if ($this->view !== 'survey') {
            return;
        }

        $this->answers[$questionId] = $value;
    }

    public function submitSurvey(): void
    {
        if (! in_array($this->view, ['survey', 'success'], true)) {
            return;
        }

        $survey = $this->survey;

        if ($survey) {
            foreach ($this->questions as $question) {
                $this->validateCurrentAnswer($question);
            }

            app(RecordSurveyResponse::class)->handle(
                $survey,
                $this->answers,
                $this->aid,
                $this->aid->beneficiary,
                request()->ip(),
                $this->confirmation,
            );
        }

        $this->view = 'done';
    }

    public function skipSurvey(): void
    {
        if (! in_array($this->view, ['success', 'survey'], true)) {
            return;
        }

        $this->view = 'done';
    }

    private function validateCurrentAnswer(SurveyQuestion $question): void
    {
        $config = array_merge($question->config ?? [], ['options' => $question->options ?? []]);
        $rules = $question->type->validationRules($question->is_required, $config);

        $fieldRules = ["answers.{$question->id}" => $rules['value'] ?? ['nullable']];

        if (isset($rules['value.*'])) {
            $fieldRules["answers.{$question->id}.*"] = $rules['value.*'];
        }

        $this->validate($fieldRules, [], [
            "answers.{$question->id}" => $question->label,
        ]);
    }

    private function logAccessFailure(AidConfirmation $confirmation, string $reason): void
    {
        activity('aid-confirmation')
            ->performedOn($confirmation)
            ->withProperties(['reason' => $reason, 'ip' => request()->ip()])
            ->log('confirmation access denied');
    }

    public function render()
    {
        return view('livewire.public.confirm-receipt');
    }
}
