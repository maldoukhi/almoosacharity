<?php

namespace App\Livewire\Beneficiaries;

use App\Enums\BeneficiaryStatus;
use App\Models\Beneficiary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Beneficiary review inbox: beneficiaries currently under review at a stage
 * the signed-in user is eligible for (holds its role or is a specific
 * assignee) — mirrors the aid approvals inbox.
 */
class ReviewInbox extends Component
{
    use WithPagination;

    public function mount(): void
    {
        Gate::authorize('beneficiaries.review');
    }

    /**
     * @return LengthAwarePaginator<int, Beneficiary>
     */
    #[Computed]
    public function beneficiaries(): LengthAwarePaginator
    {
        return $this->pendingAtMyStages()
            ->with(['currentStage', 'creator:id,name'])
            // Oldest waiting first: the beneficiary who has been sitting in
            // the workflow the longest gets priority attention.
            ->orderBy('submitted_at')
            ->paginate(15);
    }

    /**
     * Total count of beneficiaries awaiting the current user's decision —
     * used for the inbox badge.
     */
    #[Computed]
    public function pendingCount(): int
    {
        return $this->pendingAtMyStages()->count();
    }

    /**
     * @return Builder<Beneficiary>
     */
    private function pendingAtMyStages(): Builder
    {
        $user = Auth::user();
        $roleNames = $user->getRoleNames()->all();

        return Beneficiary::query()
            ->where('status', BeneficiaryStatus::UnderReview->value)
            ->whereHas('currentStage', function (Builder $query) use ($roleNames, $user): void {
                $query->where(function (Builder $inner) use ($roleNames, $user): void {
                    if ($roleNames !== []) {
                        $inner->whereIn('role', $roleNames);
                    }

                    $inner->orWhereJsonContains('assignee_user_ids', (int) $user->id);
                });
            });
    }

    public function render()
    {
        return view('livewire.beneficiaries.review-inbox');
    }
}
