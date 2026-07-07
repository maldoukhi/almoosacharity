<?php

namespace App\Livewire\Beneficiaries\Profile;

use App\Enums\IncomeSourceType;
use App\Models\Beneficiary;
use App\Models\BeneficiaryIncomeSource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * "Income sources" tab of the beneficiary profile: inline CRUD list.
 *
 * Simplification decision (documented per task spec): the beneficiary's
 * aggregate `monthly_income` column is not entered independently once
 * income sources exist — every save/delete here recomputes it as the sum
 * of all remaining income sources, so it always stays in sync with this
 * list instead of being a second, potentially inconsistent, source of
 * truth.
 */
class IncomeSources extends Component
{
    public Beneficiary $beneficiary;

    public ?int $editingId = null;

    public bool $showForm = false;

    public string $source_type = '';

    public ?float $amount = null;

    public string $notes = '';

    public function mount(Beneficiary $beneficiary): void
    {
        $this->beneficiary = $beneficiary;
    }

    /**
     * @return Collection<int, BeneficiaryIncomeSource>
     */
    #[Computed]
    public function sources(): Collection
    {
        return $this->beneficiary->incomeSources()->orderBy('created_at')->get();
    }

    /**
     * @return array<int, IncomeSourceType>
     */
    #[Computed]
    public function types(): array
    {
        return IncomeSourceType::cases();
    }

    /**
     * Formatted total of all income sources, with tabular-number-friendly
     * two-decimal precision.
     */
    #[Computed]
    public function total(): string
    {
        return number_format((float) $this->sources->sum('amount'), 2);
    }

    public function addNew(): void
    {
        Gate::authorize('update', $this->beneficiary);

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        Gate::authorize('update', $this->beneficiary);

        $source = $this->beneficiary->incomeSources()->findOrFail($id);

        $this->showForm = true;
        $this->editingId = $source->id;
        $this->source_type = $source->source_type->value;
        $this->amount = (float) $source->amount;
        $this->notes = (string) $source->notes;
    }

    public function save(): void
    {
        Gate::authorize('update', $this->beneficiary);

        $validated = $this->validate([
            'source_type' => ['required', Rule::enum(IncomeSourceType::class)],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        if ($this->editingId) {
            $this->beneficiary->incomeSources()->whereKey($this->editingId)->update($validated);
        } else {
            $this->beneficiary->incomeSources()->create($validated);
        }

        $this->syncMonthlyIncomeTotal();

        $this->resetForm();

        $this->dispatch('toast', type: 'success', message: __('beneficiaries.messages.income_source_saved'));
    }

    public function delete(int $id): void
    {
        Gate::authorize('update', $this->beneficiary);

        $this->beneficiary->incomeSources()->whereKey($id)->delete();

        $this->syncMonthlyIncomeTotal();

        $this->dispatch('toast', type: 'success', message: __('beneficiaries.messages.income_source_deleted'));
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    private function syncMonthlyIncomeTotal(): void
    {
        unset($this->sources);

        $this->beneficiary->update([
            'monthly_income' => $this->sources->sum('amount'),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'showForm', 'source_type', 'amount', 'notes']);
    }

    public function render()
    {
        return view('livewire.beneficiaries.profile.income-sources');
    }
}
