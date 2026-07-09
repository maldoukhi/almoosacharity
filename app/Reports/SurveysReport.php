<?php

namespace App\Reports;

use App\Enums\AidStatus;
use App\Enums\SurveyQuestionType;
use App\Livewire\Surveys\Results;
use App\Models\Aid;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Reports\Contracts\Report;
use App\Reports\Filters\SurveysFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Survey results report: per survey — total responses, an (optionally
 * derivable) response rate, and per-question aggregates shaped by question
 * type. Every aggregate is computed in PHP over the loaded answers (no
 * MySQL-only SQL), matching the read-only survey results screen
 * ({@see Results}) so the numbers line up.
 *
 * The flat Excel/PDF export is derived from the same per-survey structure
 * via {@see rows()}: one row per option/bucket/sample, in the shape
 * survey | question | type | detail | count | percentage.
 */
final class SurveysReport implements Report
{
    /** How many free-text samples to surface per text question. */
    private const TEXT_SAMPLE_LIMIT = 5;

    /** Character cap for a truncated free-text sample in the export. */
    private const TEXT_SAMPLE_TRUNCATE = 80;

    public function __construct(private readonly SurveysFilter $filter) {}

    public function authorize(): bool
    {
        return Gate::allows('reports.view');
    }

    /**
     * The surveys in scope (the required-or-all survey selector plus the
     * optional program filter). Soft-deleted surveys are excluded by the
     * model's default scope.
     *
     * @return Builder<Survey>
     */
    public function surveysQuery(): Builder
    {
        return Survey::query()
            ->when($this->filter->surveyId, fn (Builder $q) => $q->whereKey($this->filter->surveyId))
            ->when($this->filter->programId, fn (Builder $q) => $q->where('aid_program_id', $this->filter->programId))
            ->orderBy('title');
    }

    /**
     * Report-contract query: the responses in scope. Used for the responses
     * total and to satisfy count()/paginate() consumers — the displayed and
     * exported rows come from surveys()/rows() instead.
     *
     * @return Builder<SurveyResponse>
     */
    public function query(): Builder
    {
        return SurveyResponse::query()
            ->whereIn('survey_id', $this->surveysQuery()->select('id'))
            ->when($this->filter->from, fn (Builder $q) => $q->whereDate('submitted_at', '>=', $this->filter->from))
            ->when($this->filter->to, fn (Builder $q) => $q->whereDate('submitted_at', '<=', $this->filter->to))
            ->latest('submitted_at');
    }

    /**
     * One aggregate summary per in-scope survey.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function surveys(): Collection
    {
        return $this->surveysQuery()
            ->with(['questions' => fn ($q) => $q->orderBy('position'), 'program:id,name'])
            ->get()
            ->map(fn (Survey $survey): array => $this->summarize($survey))
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function summarize(Survey $survey): array
    {
        $responseIds = SurveyResponse::query()
            ->where('survey_id', $survey->id)
            ->when($this->filter->from, fn (Builder $q) => $q->whereDate('submitted_at', '>=', $this->filter->from))
            ->when($this->filter->to, fn (Builder $q) => $q->whereDate('submitted_at', '<=', $this->filter->to))
            ->pluck('id');

        $total = $responseIds->count();

        $answersByQuestion = SurveyAnswer::query()
            ->whereIn('survey_response_id', $responseIds)
            ->with('response:id,submitted_at')
            ->get()
            ->groupBy('survey_question_id');

        $questions = $survey->questions->map(function (SurveyQuestion $question) use ($answersByQuestion): array {
            $answered = ($answersByQuestion->get($question->id) ?? collect())
                ->filter(fn (SurveyAnswer $answer): bool => $answer->value !== null && $answer->value !== []);

            return $this->summarizeQuestion($question, $answered);
        })->values();

        return [
            'survey' => $survey,
            'program' => $survey->program?->name,
            'total_responses' => $total,
            'response_rate' => $this->responseRate($survey, $total),
            'questions' => $questions,
        ];
    }

    /**
     * @param  Collection<int, SurveyAnswer>  $answered
     * @return array<string, mixed>
     */
    private function summarizeQuestion(SurveyQuestion $question, Collection $answered): array
    {
        $base = [
            'question' => $question,
            'label' => $question->label,
            'type' => $question->type,
            'type_label' => $question->type->label(),
            'total' => $answered->count(),
        ];

        return match ($question->type) {
            SurveyQuestionType::SingleChoice,
            SurveyQuestionType::MultipleChoice => $base + $this->choiceSummary($question, $answered),
            SurveyQuestionType::Rating => $base + $this->ratingSummary($question, $answered),
            SurveyQuestionType::YesNo => $base + $this->yesNoSummary($answered),
            SurveyQuestionType::ShortText,
            SurveyQuestionType::LongText => $base + $this->textSummary($answered),
        };
    }

    /**
     * @param  Collection<int, SurveyAnswer>  $answered
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

        $options = collect($question->options ?? [])->map(fn (array $option): array => [
            'label' => $option['label'],
            'count' => $count = $counts[$option['value']] ?? 0,
            'percentage' => $total > 0 ? (int) round($count / $total * 100) : 0,
        ])->values();

        return [
            'kind' => 'choice',
            'options' => $options,
        ];
    }

    /**
     * @param  Collection<int, SurveyAnswer>  $answered
     * @return array<string, mixed>
     */
    private function ratingSummary(SurveyQuestion $question, Collection $answered): array
    {
        $maxStars = (int) ($question->config['max_stars'] ?? 5);

        $values = $answered->pluck('value')
            ->filter(fn ($value): bool => is_numeric($value))
            ->map(fn ($value): int => (int) $value);

        $distribution = collect(range(1, $maxStars))->map(fn (int $star): array => [
            'star' => $star,
            'count' => $count = $values->filter(fn (int $value): bool => $value === $star)->count(),
            'percentage' => $values->count() > 0 ? (int) round($count / $values->count() * 100) : 0,
        ]);

        return [
            'kind' => 'rating',
            'average' => $values->isNotEmpty() ? round($values->avg(), 1) : null,
            'min' => $values->isNotEmpty() ? $values->min() : null,
            'max' => $values->isNotEmpty() ? $values->max() : null,
            'max_stars' => $maxStars,
            'distribution' => $distribution,
        ];
    }

    /**
     * @param  Collection<int, SurveyAnswer>  $answered
     * @return array<string, mixed>
     */
    private function yesNoSummary(Collection $answered): array
    {
        $total = $answered->count();
        $yes = $answered->filter(fn (SurveyAnswer $answer): bool => $answer->value === true)->count();
        $no = $total - $yes;

        return [
            'kind' => 'yes_no',
            'yes' => $yes,
            'no' => $no,
            'yes_percentage' => $total > 0 ? (int) round($yes / $total * 100) : 0,
            'no_percentage' => $total > 0 ? (int) round($no / $total * 100) : 0,
        ];
    }

    /**
     * @param  Collection<int, SurveyAnswer>  $answered
     * @return array<string, mixed>
     */
    private function textSummary(Collection $answered): array
    {
        $latest = $answered
            ->sortByDesc(fn (SurveyAnswer $answer) => $answer->response?->submitted_at)
            ->take(self::TEXT_SAMPLE_LIMIT)
            ->map(fn (SurveyAnswer $answer): array => [
                'text' => Str::limit((string) $answer->value, self::TEXT_SAMPLE_TRUNCATE),
                'submitted_at' => $answer->response?->submitted_at,
            ])
            ->values();

        return [
            'kind' => 'text',
            'latest' => $latest,
        ];
    }

    /**
     * A best-effort response rate: only derivable for a program-scoped
     * survey, as responses over the count of aids in that program that
     * reached the beneficiary (Delivered/Confirmed) — i.e. those whose
     * beneficiaries could have been prompted for the survey. Clamped to
     * 100% and null when there is nothing to divide by (general survey,
     * zero responses, or no eligible aids).
     */
    private function responseRate(Survey $survey, int $responses): ?int
    {
        if ($survey->aid_program_id === null || $responses === 0) {
            return null;
        }

        $eligible = Aid::query()
            ->where('aid_program_id', $survey->aid_program_id)
            ->whereIn('status', [AidStatus::Delivered->value, AidStatus::Confirmed->value])
            ->count();

        if ($eligible === 0) {
            return null;
        }

        return (int) min(100, round($responses / $eligible * 100));
    }

    /**
     * Flat rows for the Excel/PDF export, derived from the per-survey
     * aggregates: one row per option/bucket/sample.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(): Collection
    {
        $rows = collect();

        foreach ($this->surveys() as $summary) {
            $surveyTitle = $summary['survey']->title;

            if ($summary['questions']->isEmpty()) {
                $rows->push($this->row($surveyTitle, __('reports.surveys.no_questions'), '', '', '', ''));

                continue;
            }

            foreach ($summary['questions'] as $question) {
                foreach ($this->questionRows($surveyTitle, $question) as $row) {
                    $rows->push($row);
                }
            }
        }

        return $rows->values();
    }

    /**
     * @param  array<string, mixed>  $question
     * @return Collection<int, array<string, mixed>>
     */
    private function questionRows(string $survey, array $question): Collection
    {
        $rows = collect();
        $label = $question['label'];
        $type = $question['type_label'];

        switch ($question['kind']) {
            case 'choice':
                if ($question['options']->isEmpty()) {
                    $rows->push($this->row($survey, $label, $type, __('reports.surveys.no_responses'), 0, ''));
                    break;
                }
                foreach ($question['options'] as $option) {
                    $rows->push($this->row($survey, $label, $type, $option['label'], $option['count'], $option['percentage'].'%'));
                }
                break;

            case 'rating':
                $rows->push($this->row(
                    $survey,
                    $label,
                    $type,
                    __('reports.surveys.metric_average'),
                    $question['average'] ?? __('common.dash'),
                    '',
                ));
                foreach ($question['distribution'] as $bucket) {
                    $rows->push($this->row($survey, $label, $type, $bucket['star'].' ★', $bucket['count'], $bucket['percentage'].'%'));
                }
                break;

            case 'yes_no':
                $rows->push($this->row($survey, $label, $type, __('reports.surveys.yes'), $question['yes'], $question['yes_percentage'].'%'));
                $rows->push($this->row($survey, $label, $type, __('reports.surveys.no'), $question['no'], $question['no_percentage'].'%'));
                break;

            default: // text
                $rows->push($this->row($survey, $label, $type, __('reports.surveys.responses_count'), $question['total'], ''));
                foreach ($question['latest'] as $sample) {
                    $rows->push($this->row($survey, $label, $type, $sample['text'], '', ''));
                }
                break;
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function row(string $survey, string $question, string $type, mixed $detail, mixed $count, string $percentage): array
    {
        return [
            'survey' => $survey,
            'question' => $question,
            'type' => $type,
            'detail' => $detail,
            'count' => $count,
            'percentage' => $percentage,
        ];
    }

    public function headings(): array
    {
        return [
            __('reports.surveys.column_survey'),
            __('reports.surveys.column_question'),
            __('reports.surveys.column_type'),
            __('reports.surveys.column_detail'),
            __('reports.surveys.column_count'),
            __('reports.surveys.column_percentage'),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function map($row): array
    {
        return [
            $row['survey'],
            $row['question'],
            $row['type'],
            $row['detail'],
            $row['count'],
            $row['percentage'],
        ];
    }

    public function totals(): array
    {
        return [
            'surveys' => $this->surveysQuery()->count(),
            'responses' => $this->query()->count(),
        ];
    }

    public function title(): string
    {
        return __('reports.surveys.title');
    }

    public function filename(string $ext): string
    {
        return 'surveys-report-'.now()->format('Y-m-d').'.'.$ext;
    }

    public function pdfView(): string
    {
        return 'reports.pdf.surveys';
    }
}
