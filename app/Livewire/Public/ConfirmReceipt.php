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
 * short link sent when an aid's disbursement is delivered (see
 * {@see CreateConfirmationOnDelivery}). No sensitive data
 * (cash amount, IBAN, national id...) is ever exposed here — only the
 * non-sensitive delivery summary needed for the beneficiary to recognise
 * what they're confirming.
 *
 * The raw token in the URL path is the sole secret: it is looked up by its
 * sha256 digest (never persisted in the clear), so an unknown/tampered
 * token resolves to no row and lands on the friendly `not_found` state
 * rather than leaking whether any given link exists. Expiry is enforced by
 * the model itself here (the `expired` state), since there is no longer a
 * signed-URL middleware in front of the route.
 */
#[Layout('layouts::public')]
class ConfirmReceipt extends Component
{
    #[Locked]
    public ?AidConfirmation $confirmation = null;

    /**
     * confirm | already | expired | success | survey | done | not_found
     */
    #[Locked]
    public string $view = 'confirm';

    /**
     * Optional signature captured on the confirm step as a
     * `data:image/png;base64,…` string from the canvas.
     */
    public string $signature = '';

    /**
     * Answers keyed by survey_question_id.
     *
     * @var array<int, mixed>
     */
    public array $answers = [];

    public int $step = 0;

    public function mount(string $token): void
    {
        $confirmation = $token === ''
            ? null
            : AidConfirmation::query()
                ->where('token_hash', AidConfirmation::hashToken($token))
                ->first();

        if ($confirmation === null) {
            $this->logAccessFailure('invalid or unknown token');

            $this->view = 'not_found';

            return;
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
            $this->signature,
        );

        $this->confirmation->refresh();

        $this->signature = '';

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

        // A survey flagged is_required cannot be bypassed: the beneficiary
        // must complete it. The UI hides the skip control in this case, so
        // this is the server-side guard against a crafted skip request.
        if ($this->survey?->is_required) {
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

    private function logAccessFailure(string $reason): void
    {
        // No AidConfirmation to attach the entry to (the token matched no
        // row), so this is a subject-less audit entry rather than one
        // performedOn a specific confirmation.
        activity('aid-confirmation')
            ->withProperties(['reason' => $reason, 'ip' => request()->ip()])
            ->log('confirmation access denied');
    }

    public function render()
    {
        return view('livewire.public.confirm-receipt');
    }
}
