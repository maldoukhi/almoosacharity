<?php

namespace App\Livewire\Surveys;

use App\Enums\SurveyQuestionType;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Read-only survey results screen: per-question aggregates rendered as
 * simple CSS percentage bars (no charting library here — ApexCharts is
 * reserved for the dashboard/reports phase).
 */
class Results extends Component
{
    public Survey $survey;

    public function mount(Survey $survey): void
    {
        $this->survey = $survey;

        Gate::authorize('viewResults', $survey);

        $this->survey->load(['questions' => fn ($query) => $query->orderBy('position')]);
    }

    #[Computed]
    public function totalResponses(): int
    {
        return $this->survey->responses()->count();
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
