<?php

namespace App\Livewire\Approvals;

use App\Enums\AidStatus;
use App\Enums\ApprovalStageType;
use App\Models\Aid;
use App\Models\AidProgram;
use App\Models\ApprovalDecision;
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
 * Approvals inbox: aids currently under review at a stage whose role the
 * signed-in user holds.
 */
class Inbox extends Component
{
    use WithPagination;

    public string $programFilter = '';

    /**
     * Which list is shown: 'pending' (aids awaiting the user's decision —
     * the default queue) or 'history' (decisions the user has already made).
     */
    #[Url]
    public string $view = 'pending';

    public function mount(): void
    {
        Gate::authorize('approvals.view');

        if (! in_array($this->view, ['pending', 'history'], true)) {
            $this->view = 'pending';
        }
    }

    public function updatingProgramFilter(): void
    {
        $this->resetPage();
    }

    public function updatingView(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Aid>
     */
    #[Computed]
    public function aids(): LengthAwarePaginator
    {
        return $this->pendingAtMyStages()
            ->with([
                'beneficiary:id,first_name,second_name,third_name,last_name',
                'program',
                'currentStage',
            ])
            ->when($this->programFilter !== '', function (Builder $query): void {
                $query->where('aid_program_id', $this->programFilter);
            })
            // Oldest waiting first: the aid that has been sitting in the
            // workflow the longest gets priority attention.
            ->orderBy('submitted_at')
            ->paginate(15);
    }

    /**
     * The decisions the signed-in user has already recorded, newest first —
     * their own processing history. Keyed off ApprovalDecision.user_id, the
     * column RecordApprovalDecision stamps with the acting user's id.
     *
     * @return LengthAwarePaginator<int, ApprovalDecision>
     */
    #[Computed]
    public function decisions(): LengthAwarePaginator
    {
        return ApprovalDecision::query()
            ->where('user_id', Auth::id())
            ->with([
                'aid:id,reference,title,beneficiary_id,aid_program_id',
                'aid.beneficiary:id,first_name,second_name,third_name,last_name',
                'aid.program',
                'stage',
            ])
            ->when($this->programFilter !== '', function (Builder $query): void {
                $query->whereHas('aid', function (Builder $aidQuery): void {
                    $aidQuery->where('aid_program_id', $this->programFilter);
                });
            })
            ->latest('decided_at')
            ->paginate(15);
    }

    /**
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
     * Total count of aids awaiting the current user's decision, regardless
     * of the program filter — used for the inbox badge.
     */
    #[Computed]
    public function pendingCount(): int
    {
        return $this->pendingAtMyStages()->count();
    }

    /**
     * @return Builder<Aid>
     */
    private function pendingAtMyStages(): Builder
    {
        $roleNames = Auth::user()->getRoleNames();

        return Aid::query()
            ->where('status', AidStatus::UnderReview->value)
            ->whereHas('currentStage', function (Builder $query) use ($roleNames): void {
                $query
                    ->whereIn('role', $roleNames)
                    // A beneficiary_response stage waits on the beneficiary,
                    // not staff: it must never surface as an actionable item
                    // in the approvals inbox.
                    ->where('type', '!=', ApprovalStageType::BeneficiaryResponse->value);
            });
    }

    public function render()
    {
        return view('livewire.approvals.inbox');
    }
}
