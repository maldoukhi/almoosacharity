<?php

namespace App\Livewire\Beneficiaries;

use App\Actions\Beneficiaries\DeleteBeneficiary;
use App\Actions\Beneficiaries\RestoreBeneficiary;
use App\Models\Beneficiary;
use App\Models\BeneficiaryCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Beneficiaries listing screen: search, filters, soft-delete/restore.
 */
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $categoryFilter = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $cityFilter = '';

    public bool $trashed = false;

    /**
     * Ids checked via the row/header checkboxes, used only by the
     * "send message" quick action (see {@see sendBroadcast()}) — cleared
     * whenever the filtered result set changes so a stale selection never
     * silently carries over to a different filter view.
     *
     * @var array<int, int>
     */
    public array $selected = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', Beneficiary::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->selected = [];
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
        $this->selected = [];
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
        $this->selected = [];
    }

    public function updatingCityFilter(): void
    {
        $this->resetPage();
        $this->selected = [];
    }

    public function updatingTrashed(): void
    {
        $this->resetPage();
        $this->selected = [];
    }

    /**
     * Header checkbox: selects every row on the current page, or clears
     * them if they're all already selected.
     */
    public function toggleSelectAllOnPage(): void
    {
        $pageIds = $this->beneficiaries->pluck('id')->all();

        $allSelected = $pageIds !== [] && array_diff($pageIds, $this->selected) === [];

        $this->selected = $allSelected
            ? array_values(array_diff($this->selected, $pageIds))
            : array_values(array_unique(array_merge($this->selected, $pageIds)));
    }

    /**
     * Quick action from the listing: hand the checked ids off to the
     * standalone bulk-messaging screen rather than sending from here.
     */
    public function sendBroadcast()
    {
        Gate::authorize('messages.broadcast');

        if ($this->selected === []) {
            return null;
        }

        return $this->redirectRoute('admin.messaging.broadcast', ['ids' => implode(',', $this->selected)], navigate: true);
    }

    /**
     * @return LengthAwarePaginator<int, Beneficiary>
     */
    #[Computed]
    public function beneficiaries(): LengthAwarePaginator
    {
        return Beneficiary::query()
            ->select($this->listColumns())
            ->with('categories')
            ->when($this->trashed, function (Builder $query): void {
                // Viewing the trashed list requires the restore permission;
                // checked here rather than per-row since there is no single
                // beneficiary instance to authorize against yet.
                Gate::authorize('beneficiaries.restore');

                $query->onlyTrashed();
            })
            ->when($this->search !== '', function (Builder $query): void {
                $term = "%{$this->search}%";

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('first_name', 'like', $term)
                        ->orWhere('second_name', 'like', $term)
                        ->orWhere('third_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('national_id', 'like', $term)
                        ->orWhere('mobile', 'like', $term);
                });
            })
            ->when($this->categoryFilter !== '', function (Builder $query): void {
                $query->whereHas('categories', function (Builder $query): void {
                    $query->where('beneficiary_categories.id', $this->categoryFilter);
                });
            })
            ->when($this->statusFilter !== '', function (Builder $query): void {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->cityFilter !== '', function (Builder $query): void {
                $query->where('city', $this->cityFilter);
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15);
    }

    /**
     * Explicit column allow-list for the listing query: every fillable
     * beneficiary attribute except the encrypted bank fields, plus the
     * standard id/timestamp columns. Built from the model's fillable list
     * (rather than hard-coded) so newly added non-bank fields are picked up
     * automatically, while iban/bank_account_holder can never leak into a
     * list query by omission.
     *
     * @return array<int, string>
     */
    private function listColumns(): array
    {
        return collect((new Beneficiary)->getFillable())
            ->reject(fn (string $column): bool => in_array($column, ['iban', 'bank_account_holder'], true))
            ->push('id')
            ->push('created_at')
            ->push('updated_at')
            ->push('deleted_at')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, BeneficiaryCategory>
     */
    #[Computed]
    public function categories(): Collection
    {
        return BeneficiaryCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Distinct, non-empty list of cities currently in use by beneficiaries.
     *
     * @return Collection<int, string>
     */
    #[Computed]
    public function cities(): Collection
    {
        return Beneficiary::query()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');
    }

    public function delete(int $id): void
    {
        $beneficiary = Beneficiary::findOrFail($id);

        Gate::authorize('delete', $beneficiary);

        app(DeleteBeneficiary::class)->handle($beneficiary);

        $this->dispatch('toast', type: 'success', message: __('beneficiaries.messages.deleted'));
    }

    public function restore(int $id): void
    {
        $beneficiary = Beneficiary::onlyTrashed()->findOrFail($id);

        Gate::authorize('restore', $beneficiary);

        app(RestoreBeneficiary::class)->handle($beneficiary);

        $this->dispatch('toast', type: 'success', message: __('beneficiaries.messages.restored'));
    }

    public function render()
    {
        return view('livewire.beneficiaries.index');
    }
}
