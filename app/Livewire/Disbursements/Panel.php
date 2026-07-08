<?php

namespace App\Livewire\Disbursements;

use App\Actions\Disbursements\ConfirmDisbursement;
use App\Actions\Disbursements\RecordDelivery;
use App\Actions\Disbursements\StartDisbursement;
use App\Enums\DisbursementMethod;
use App\Exceptions\InvalidAidTransitionException;
use App\Models\Aid;
use App\Models\Disbursement;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Disbursement panel nested into the aid detail screen (Aids\Show): method
 * selection and start (Approved), delivery recording (InDisbursement) and
 * the second administrative confirmation (Delivered) for a single aid's
 * disbursement.
 */
class Panel extends Component
{
    use WithFileUploads;

    #[Locked]
    public Aid $aid;

    public string $method = '';

    /** Whether the start-confirmation modal is open. */
    public bool $showStartConfirm = false;

    /** Captured signature as a data: URL (optional), saved on start. */
    public string $signature = '';

    public string $reference = '';

    public string $courierName = '';

    public ?string $deliveredAt = null;

    public string $notes = '';

    public mixed $proof = null;

    /**
     * The aid's single disbursement row, if disbursing has started.
     */
    #[Computed]
    public function disbursement(): ?Disbursement
    {
        return $this->aid->disbursement()
            ->with(['startedBy', 'deliveredBy', 'confirmedBy'])
            ->first();
    }

    #[Computed]
    public function canStart(): bool
    {
        return Gate::allows('start', [Disbursement::class, $this->aid]);
    }

    #[Computed]
    public function canRecord(): bool
    {
        return $this->disbursement !== null && Gate::allows('record', $this->disbursement);
    }

    #[Computed]
    public function canConfirm(): bool
    {
        return $this->disbursement !== null && Gate::allows('confirm', $this->disbursement);
    }

    /**
     * Delivery methods offered when starting disbursement: bank transfer
     * is excluded when the beneficiary has no IBAN on file.
     *
     * @return array<int, DisbursementMethod>
     */
    #[Computed]
    public function availableMethods(): array
    {
        return collect(DisbursementMethod::cases())
            ->reject(fn (DisbursementMethod $method): bool => $method->requiresBankAccount() && ! $this->aid->beneficiary->iban)
            ->values()
            ->all();
    }

    /**
     * Masked IBAN preview for the bank-transfer option: the disbursement's
     * own masked snapshot once started, otherwise a live preview computed
     * off the beneficiary's current IBAN. Never the decrypted value.
     */
    #[Computed]
    public function bankHint(): ?string
    {
        return $this->disbursement?->bank_account_masked ?? $this->aid->beneficiary->maskedIban();
    }

    /**
     * Validate a method is picked and, if so, open the start-confirmation
     * modal (deliverer/recipient summary + optional signature).
     */
    public function confirmStart(): void
    {
        Gate::authorize('start', [Disbursement::class, $this->aid]);

        $this->validate([
            'method' => ['required', 'string', 'in:'.implode(',', array_column(DisbursementMethod::cases(), 'value'))],
        ]);

        $this->showStartConfirm = true;
    }

    public function cancelStart(): void
    {
        $this->showStartConfirm = false;
        $this->signature = '';
    }

    public function start(): void
    {
        Gate::authorize('start', [Disbursement::class, $this->aid]);

        $this->validate([
            'method' => ['required', 'string', 'in:'.implode(',', array_column(DisbursementMethod::cases(), 'value'))],
        ]);

        try {
            $disbursement = app(StartDisbursement::class)->handle(
                $this->aid,
                DisbursementMethod::from($this->method),
                Auth::user(),
            );
        } catch (InvalidAidTransitionException|AuthorizationException $exception) {
            $this->showStartConfirm = false;
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());

            return;
        }

        $this->attachSignature($disbursement);

        $this->showStartConfirm = false;
        $this->signature = '';

        $this->dispatch('toast', type: 'success', message: __('disbursements.messages.started'));

        $this->refreshPanel();

        $this->dispatch('disbursement-updated');
    }

    /**
     * Persist the captured signature (a `data:image/png;base64,…` string
     * from the canvas) onto the disbursement, if one was drawn.
     */
    private function attachSignature(Disbursement $disbursement): void
    {
        $signature = trim($this->signature);

        if ($signature === '' || ! str_starts_with($signature, 'data:image/')) {
            return;
        }

        $base64 = preg_replace('#^data:image/\w+;base64,#', '', $signature) ?? '';

        if ($base64 === '') {
            return;
        }

        $disbursement->addMediaFromBase64($base64)
            ->usingFileName('signature-'.$disbursement->id.'.png')
            ->toMediaCollection('start_signature');
    }

    public function record(): void
    {
        $disbursement = $this->disbursement;

        if (! $disbursement) {
            abort(404);
        }

        Gate::authorize('record', $disbursement);

        $isBankTransfer = $disbursement->method === DisbursementMethod::BankTransfer;
        $isCourier = $disbursement->method === DisbursementMethod::Courier;

        $validated = $this->validate([
            'reference' => ['required', 'string', 'max:100'],
            'courierName' => [$isCourier ? 'required' : 'nullable', 'string', 'max:150'],
            'deliveredAt' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'reference.required' => __('validation.custom.disbursement.reference_required'),
            'courierName.required' => __('validation.custom.disbursement.courier_name_required'),
        ]);

        try {
            app(RecordDelivery::class)->handle($disbursement, Auth::user(), [
                'delivered_at' => $validated['deliveredAt'] ?: now(),
                'transfer_reference' => $isBankTransfer ? $validated['reference'] : null,
                'receipt_number' => ! $isBankTransfer ? $validated['reference'] : null,
                'courier_name' => $isCourier ? $validated['courierName'] : null,
                'notes' => $validated['notes'] ?: null,
                'proof' => $validated['proof'] ?? null,
            ]);
        } catch (InvalidAidTransitionException|AuthorizationException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());

            return;
        }

        $this->dispatch('toast', type: 'success', message: __('disbursements.messages.delivered'));

        $this->reset(['reference', 'courierName', 'deliveredAt', 'notes', 'proof']);

        $this->refreshPanel();

        $this->dispatch('disbursement-updated');
    }

    public function confirm(): void
    {
        $disbursement = $this->disbursement;

        if (! $disbursement) {
            abort(404);
        }

        Gate::authorize('confirm', $disbursement);

        try {
            app(ConfirmDisbursement::class)->handle($disbursement, Auth::user());
        } catch (InvalidAidTransitionException|AuthorizationException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());

            return;
        }

        $this->dispatch('toast', type: 'success', message: __('disbursements.messages.confirmed'));

        $this->refreshPanel();
    }

    private function refreshPanel(): void
    {
        $this->aid->refresh();

        unset(
            $this->disbursement,
            $this->canStart,
            $this->canRecord,
            $this->canConfirm,
            $this->availableMethods,
            $this->bankHint,
        );
    }

    public function render()
    {
        return view('livewire.disbursements.panel');
    }
}
