<?php

namespace App\Livewire\Aids;

use App\Actions\Aids\CancelAid;
use App\Actions\Aids\DeleteAid;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Enums\ReceiptStatus;
use App\Exceptions\InvalidAidTransitionException;
use App\Models\Aid;
use App\Models\AidProgram;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Aids listing screen: search, filters, and row actions (delete/cancel).
 */
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $programFilter = '';

    #[Url]
    public string $typeFilter = '';

    #[Url]
    public string $beneficiaryFilter = '';

    /**
     * Filters aids by the receipt outcome the beneficiary reported on the
     * public confirmation page (partial / not received) — the "needs
     * follow-up" surface for staff.
     */
    #[Url]
    public string $receiptFilter = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Aid::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingProgramFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingBeneficiaryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingReceiptFilter(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Aid>
     */
    #[Computed]
    public function aids(): LengthAwarePaginator
    {
        $user = Auth::user();

        return Aid::query()
            ->with([
                'beneficiary:id,first_name,second_name,third_name,last_name',
                'program',
                'currentStage',
                'confirmation:id,aid_id,receipt_status',
            ])
            ->withCount('items')
            ->when(! $user->can('aids.view-any'), function (Builder $query) use ($user): void {
                // Without aids.view-any a user only sees the aids they
                // themselves created.
                $query->where('created_by', $user->id);
            })
            ->when($this->search !== '', function (Builder $query): void {
                $query->where('reference', 'like', "%{$this->search}%");
            })
            ->when($this->statusFilter !== '', function (Builder $query): void {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->programFilter !== '', function (Builder $query): void {
                $query->where('aid_program_id', $this->programFilter);
            })
            ->when($this->typeFilter !== '', function (Builder $query): void {
                $query->where('type', $this->typeFilter);
            })
            ->when($this->beneficiaryFilter !== '', function (Builder $query): void {
                $query->where('beneficiary_id', $this->beneficiaryFilter);
            })
            ->when($this->receiptFilter !== '', function (Builder $query): void {
                $query->whereHas('confirmation', function (Builder $sub): void {
                    $sub->where('receipt_status', $this->receiptFilter);
                });
            })
            ->latest()
            ->paginate(15);
    }

    /**
     * Active aid programs, for the program filter dropdown.
     *
     * @return Collection<int, AidProgram>
     */
    #[Computed]
    public function programs(): Collection
    {
        return AidProgram::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @return Collection<int, AidStatus>
     */
    #[Computed]
    public function statuses(): Collection
    {
        return collect(AidStatus::cases());
    }

    /**
     * @return array<int, AidType>
     */
    #[Computed]
    public function types(): array
    {
        return AidType::cases();
    }

    /**
     * Receipt outcomes that warrant staff follow-up, for the receipt filter
     * dropdown (a full receipt is deliberately omitted — it needs no action).
     *
     * @return array<int, ReceiptStatus>
     */
    #[Computed]
    public function receiptStatuses(): array
    {
        return array_values(array_filter(
            ReceiptStatus::cases(),
            fn (ReceiptStatus $status): bool => $status->needsAttention(),
        ));
    }

    public function delete(int $aidId): void
    {
        $aid = Aid::findOrFail($aidId);

        Gate::authorize('delete', $aid);

        try {
            app(DeleteAid::class)->handle($aid);
        } catch (InvalidAidTransitionException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());

            return;
        }

        unset($this->aids);

        $this->dispatch('toast', type: 'success', message: __('aids.messages.deleted'));
    }

    public function cancel(int $aidId): void
    {
        $aid = Aid::findOrFail($aidId);

        Gate::authorize('cancel', $aid);

        try {
            app(CancelAid::class)->handle($aid);
        } catch (InvalidAidTransitionException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());

            return;
        }

        unset($this->aids);

        $this->dispatch('toast', type: 'success', message: __('aids.messages.cancelled'));
    }

    public function render()
    {
        return view('livewire.aids.index');
    }
}
