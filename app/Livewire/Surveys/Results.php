<?php

namespace App\Livewire\Surveys;

use App\Enums\SurveyQuestionType;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Read-only survey results screen. Two views over the same, gate-guarded
 * data set:
 *   - "aggregate": per-question summaries rendered as simple CSS percentage
 *     bars (no charting library here — ApexCharts is reserved for the
 *     dashboard/reports phase);
 *   - "individual": every submitted response listed with its respondent's
 *     short name, submission date and (when tied to one) the related aid,
 *     each expandable to reveal that beneficiary's full per-question answers.
 *
 * Everything is scoped through the survey's own responses()/questions()
 * relationships so a response belonging to another survey can never leak in.
 */
class Results extends Component
{
    use WithPagination;

    public Survey $survey;

    /**
     * Which panel is shown: "aggregate" (default) or "individual".
     */
    #[Url]
    public string $view = 'aggregate';

    /**
     * The individual response currently expanded in the "individual" view,
     * or null when the list is collapsed.
     */
    public ?int $selectedResponseId = null;

    public function mount(Survey $survey): void
    {
        $this->survey = $survey;

        Gate::authorize('viewResults', $survey);

        $this->survey->load(['questions' => fn ($query) => $query->orderBy('position')]);
    }

    /**
     * Switch between the aggregate and individual panels, collapsing any
     * open response and resetting pagination for a clean list.
     */
    public function switchView(string $view): void
    {
        $this->view = in_array($view, ['aggregate', 'individual'], true) ? $view : 'aggregate';
        $this->selectedResponseId = null;
        $this->resetPage();
    }

    /**
     * Expand a response's answers, or collapse it when it's already open.
     */
    public function toggleResponse(int $responseId): void
    {
        $this->selectedResponseId = $this->selectedResponseId === $responseId ? null : $responseId;
    }

    #[Computed]
    public function totalResponses(): int
    {
        return $this->survey->responses()->count();
    }

    /**
     * Every submitted response for this survey, newest first, with the
     * respondent and (optional) related aid eager-loaded for the list rows.
     *
     * @return LengthAwarePaginator<int, SurveyResponse>
     */
    #[Computed]
    public function responses(): LengthAwarePaginator
    {
        return $this->survey->responses()
            ->with(['beneficiary', 'aid'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->paginate(15);
    }

    /**
     * The currently expanded response's answer to every question, shaped by
     * type for display (identical presentation to the aid-detail survey
     * card). Empty when nothing is expanded or the id doesn't belong to this
     * survey.
     *
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function selectedResponseDetail(): Collection
    {
        if ($this->selectedResponseId === null) {
            return collect();
        }

        // Scoped through the survey's own responses(): a response id from any
        // other survey resolves to null and renders nothing.
        $response = $this->survey->responses()
            ->with('answers')
            ->whereKey($this->selectedResponseId)
            ->first();

        if (! $response) {
            return collect();
        }

        $answers = $response->answers->keyBy('survey_question_id');

        return $this->survey->questions->map(function (SurveyQuestion $question) use ($answers): array {
            $value = $answers->get($question->id)?->value;
            $answered = $value !== null && $value !== [];

            $base = [
                'id' => $question->id,
                'label' => $question->label,
                'type_label' => $question->type->label(),
                'answered' => $answered,
            ];

            return match ($question->type) {
                SurveyQuestionType::SingleChoice,
                SurveyQuestionType::MultipleChoice => $base + [
                    'kind' => 'choice',
                    'labels' => $this->choiceLabels($question, $value),
                ],
                SurveyQuestionType::Rating => $base + [
                    'kind' => 'rating',
                    'rating' => $answered ? (int) $value : null,
                    'max_stars' => (int) ($question->config['max_stars'] ?? 5),
                ],
                SurveyQuestionType::YesNo => $base + [
                    'kind' => 'yes_no',
                    'yes' => $answered ? (bool) $value : null,
                ],
                default => $base + [
                    'kind' => 'text',
                    'text' => $answered ? (string) $value : null,
                ],
            };
        });
    }

    /**
     * Map a choice question's stored value(s) to their human labels,
     * falling back to the raw value when an option was later removed.
     *
     * @param  mixed  $value
     * @return array<int, string>
     */
    private function choiceLabels(SurveyQuestion $question, $value): array
    {
        $values = is_array($value) ? $value : ($value === null ? [] : [$value]);
        $options = collect($question->options ?? [])->keyBy('value');

        return collect($values)
            ->map(fn ($item): string => $options->get($item)['label'] ?? (string) $item)
            ->all();
    }

    /**
     * One aggregate result per question, shaped according to its type.
     *
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function perQuestion(): Collection
    {
        return $this->survey->questions->map(function (SurveyQuestion $question): array {
            $answered = $question->answers()
                ->with('response')
                ->get()
                ->filter(fn ($answer) => $answer->value !== null && $answer->value !== []);

            return match ($question->type) {
                SurveyQuestionType::SingleChoice, SurveyQuestionType::MultipleChoice => $this->choiceSummary($question, $answered),
                SurveyQuestionType::Rating => $this->ratingSummary($question, $answered),
                SurveyQuestionType::YesNo => $this->yesNoSummary($question, $answered),
                SurveyQuestionType::ShortText, SurveyQuestionType::LongText => $this->textSummary($question, $answered),
            };
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function choiceSummary(SurveyQuestion $question, Collection $answered): array
    {
        $total = $answered->count();
        $counts = [];

        foreach ($answered as $answer) {
            foreach (is_array($answer->value) ? $answer->value : [$answer->value] as $value) {
                $counts[$value] = ($counts[$value] ?? 0) + 1;
            }
        }

        $options = collect($question->options ?? [])->map(function (array $option) use ($counts, $total): array {
            $count = $counts[$option['value']] ?? 0;

            return [
                'label' => $option['label'],
                'count' => $count,
                'percentage' => $total > 0 ? (int) round($count / $total * 100) : 0,
            ];
        })->values();

        return [
            'question' => $question,
            'kind' => 'choice',
            'total' => $total,
            'options' => $options,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function ratingSummary(SurveyQuestion $question, Collection $answered): array
    {
        $maxStars = (int) ($question->config['max_stars'] ?? 5);
        $values = $answered->pluck('value')->filter(fn ($value) => is_numeric($value))->map(fn ($value) => (int) $value);

        $distribution = collect(range(1, $maxStars))->map(fn (int $star): array => [
            'star' => $star,
            'count' => $count = $values->filter(fn (int $value) => $value === $star)->count(),
            'percentage' => $values->count() > 0 ? (int) round($count / $values->count() * 100) : 0,
        ]);

        return [
            'question' => $question,
            'kind' => 'rating',
            'total' => $values->count(),
            'average' => $values->isNotEmpty() ? round($values->avg(), 1) : null,
            'max_stars' => $maxStars,
            'distribution' => $distribution,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function yesNoSummary(SurveyQuestion $question, Collection $answered): array
    {
        $total = $answered->count();
        $yes = $answered->filter(fn ($answer) => $answer->value === true)->count();
        $no = $total - $yes;

        return [
            'question' => $question,
            'kind' => 'yes_no',
            'total' => $total,
            'yes' => $yes,
            'no' => $no,
            'yes_percentage' => $total > 0 ? (int) round($yes / $total * 100) : 0,
            'no_percentage' => $total > 0 ? (int) round($no / $total * 100) : 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function textSummary(SurveyQuestion $question, Collection $answered): array
    {
        $latest = $answered
            ->sortByDesc(fn ($answer) => $answer->response?->submitted_at)
            ->take(10)
            ->map(fn ($answer): array => [
                'value' => $answer->value,
                'submitted_at' => $answer->response?->submitted_at,
            ])
            ->values();

        return [
            'question' => $question,
            'kind' => 'text',
            'total' => $answered->count(),
            'latest' => $latest,
        ];
    }

    public function render()
    {
        return view('livewire.surveys.results');
    }
}
