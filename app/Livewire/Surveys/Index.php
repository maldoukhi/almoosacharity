<?php

namespace App\Livewire\Surveys;

use App\Actions\Surveys\ToggleSurvey;
use App\Models\Survey;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Surveys listing screen: shows every survey with its question/response
 * counts and a clickable active/inactive badge, plus create/edit/results
 * row actions.
 */
class Index extends Component
{
    public function mount(): void
    {
        Gate::authorize('viewAny', Survey::class);
    }

    /**
     * @return Collection<int, Survey>
     */
    #[Computed]
    public function surveys(): Collection
    {
        return Survey::query()
            ->withCount(['questions', 'responses'])
            ->with('program')
            ->latest()
            ->get();
    }

    public function toggleActive(int $id): void
    {
        $survey = Survey::findOrFail($id);

        Gate::authorize('update', $survey);

        app(ToggleSurvey::class)->handle($survey);

        unset($this->surveys);

        $this->dispatch('toast', type: 'success', message: __('surveys.messages.toggled'));
    }

    public function delete(int $id): void
    {
        $survey = Survey::findOrFail($id);

        Gate::authorize('delete', $survey);

        if ($survey->responses()->exists()) {
            $this->dispatch('toast', type: 'error', message: __('surveys.messages.cannot_delete_has_responses'));

            return;
        }

        $survey->delete();

        unset($this->surveys);

        $this->dispatch('toast', type: 'success', message: __('surveys.messages.deleted'));
    }

    public function render()
    {
        return view('livewire.surveys.index');
    }
}
