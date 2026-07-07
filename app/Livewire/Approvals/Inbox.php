<?php

namespace App\Livewire\Approvals;

use App\Enums\AidStatus;
use App\Models\Aid;
use App\Models\AidProgram;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
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

    public function mount(): void
    {
        Gate::authorize('approvals.view');
    }

    public function updatingProgramFilter(): void
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
                $query->whereIn('role', $roleNames);
            });
    }

    public function render()
    {
        return view('livewire.approvals.inbox');
    }
}
