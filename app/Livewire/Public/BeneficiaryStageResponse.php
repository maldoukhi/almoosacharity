<?php

namespace App\Livewire\Public;

use App\Actions\Approvals\RecordBeneficiaryStageResponse;
use App\Listeners\IssueBeneficiaryStageLink;
use App\Models\Aid;
use App\Models\BeneficiaryStageResponse as StageResponse;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * The public, unauthenticated "respond to this approval stage" page reached
 * only via the short link sent when an aid enters a beneficiary_response
 * stage (see {@see IssueBeneficiaryStageLink}). No sensitive
 * data (cash amount, IBAN, national id...) is ever exposed here — only the
 * non-sensitive summary the beneficiary needs to recognise what they are
 * responding to.
 *
 * The raw token in the URL path is the sole secret: it is looked up by its
 * sha256 digest (never persisted in the clear), so an unknown/tampered
 * token resolves to no row and lands on the friendly `not_found` state.
 * Expiry and single-use are enforced by the model and the record action.
 */
#[Layout('layouts::public')]
class BeneficiaryStageResponse extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?StageResponse $response = null;

    /**
     * respond | already | expired | success | not_found
     */
    #[Locked]
    public string $view = 'respond';

    /**
     * The beneficiary's optional free-text note.
     */
    public string $note = '';

    /**
     * The beneficiary's optional supporting document (pdf/jpg/png).
     */
    public ?TemporaryUploadedFile $document = null;

    public function mount(string $token): void
    {
        $response = $token === ''
            ? null
            : StageResponse::query()
                ->where('token_hash', StageResponse::hashToken($token))
                ->first();

        if ($response === null) {
            $this->logAccessFailure('invalid or unknown token');

            $this->view = 'not_found';

            return;
        }

        $this->response = $response;

        if ($response->isResponded()) {
            $this->view = 'already';

            return;
        }

        if ($response->isExpired()) {
            $this->view = 'expired';

            return;
        }

        if ($response->opened_at === null) {
            $response->update(['opened_at' => now()]);

            activity('beneficiary-stage-response')
                ->performedOn($response)
                ->log('stage-response link opened');
        }

        $this->view = 'respond';
    }

    #[Computed]
    public function aid(): Aid
    {
        return $this->response->aid()->with(['beneficiary', 'program', 'items'])->firstOrFail();
    }

    public function submit(): void
    {
        if ($this->view !== 'respond') {
            return;
        }

        $this->validate([
            'note' => ['nullable', 'string', 'max:2000'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        // Require at least a note or a document — an empty response is
        // meaningless.
        if (trim($this->note) === '' && $this->document === null) {
            $this->addError('note', __('beneficiary_stage.response_required'));

            return;
        }

        app(RecordBeneficiaryStageResponse::class)->handle(
            $this->response,
            (string) (request()->ip() ?? '0.0.0.0'),
            (string) request()->userAgent(),
            $this->note,
            $this->document instanceof TemporaryUploadedFile ? $this->document : null,
        );

        $this->response->refresh();

        $this->note = '';
        $this->document = null;

        $this->view = 'success';
    }

    private function logAccessFailure(string $reason): void
    {
        activity('beneficiary-stage-response')
            ->withProperties(['reason' => $reason, 'ip' => request()->ip()])
            ->log('stage-response access denied');
    }

    public function render()
    {
        return view('livewire.public.beneficiary-stage-response');
    }
}
