<?php

namespace App\Livewire\Settings\Categories;

use App\Actions\Categories\CreateCategory;
use App\Actions\Categories\DeleteCategory;
use App\Actions\Categories\UpdateCategory;
use App\Models\BeneficiaryCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use RuntimeException;

/**
 * Beneficiary categories settings screen: simple inline CRUD list.
 */
class Index extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public bool $is_active = true;

    public function mount(): void
    {
        Gate::authorize('viewAny', BeneficiaryCategory::class);
    }

    /**
     * @return Collection<int, BeneficiaryCategory>
     */
    #[Computed]
    public function categories(): Collection
    {
        return BeneficiaryCategory::query()->orderBy('sort_order')->get();
    }

    public function save(): void
    {
        $category = $this->editingId ? BeneficiaryCategory::findOrFail($this->editingId) : null;

        Gate::authorize($category ? 'update' : 'create', $category ?? BeneficiaryCategory::class);

        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('beneficiary_categories', 'name')->ignore($category?->id),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        if ($category) {
            app(UpdateCategory::class)->handle($category, $validated);
        } else {
            app(CreateCategory::class)->handle($validated);
        }

        unset($this->categories);

        $this->resetForm();

        $this->dispatch('toast', type: 'success', message: __('categories.messages.saved'));
    }

    public function edit(int $id): void
    {
        $category = BeneficiaryCategory::findOrFail($id);

        Gate::authorize('update', $category);

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->description = (string) $category->description;
        $this->is_active = $category->is_active;
    }

    public function delete(int $id): void
    {
        $category = BeneficiaryCategory::findOrFail($id);

        Gate::authorize('delete', $category);

        try {
            app(DeleteCategory::class)->handle($category);

            unset($this->categories);

            $this->dispatch('toast', type: 'success', message: __('categories.messages.deleted'));
        } catch (RuntimeException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());
        }
    }

    public function toggleActive(int $id): void
    {
        $category = BeneficiaryCategory::findOrFail($id);

        Gate::authorize('update', $category);

        app(UpdateCategory::class)->handle($category, ['is_active' => ! $category->is_active]);

        unset($this->categories);
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'description', 'is_active']);
    }

    public function render()
    {
        return view('livewire.settings.categories.index');
    }
}
