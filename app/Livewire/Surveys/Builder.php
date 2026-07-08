<?php

namespace App\Livewire\Surveys;

use App\Actions\Surveys\SaveSurvey;
use App\Enums\SurveyQuestionType;
use App\Enums\SurveyScope;
use App\Exceptions\SurveyQuestionHasAnswersException;
use App\Exceptions\SurveyScopeRequiresProgramException;
use App\Models\AidProgram;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Survey create/edit form: survey-level settings plus a full question
 * list editor (add/remove/reorder questions, dynamic option lists for
 * choice questions). Saving always rewrites the whole question list
 * through {@see SaveSurvey}.
 */
class Builder extends Component
{
    public ?Survey $survey = null;

    public string $title = '';

    public string $description = '';

    public string $scope = 'general';

    public ?int $aid_program_id = null;

    public bool $is_active = false;

    public bool $is_required = false;

    public ?string $starts_at = null;

    public ?string $ends_at = null;

    /**
     * @var array<int, array{id?: int, type: string, label: string, help_text: string, is_required: bool, options: array<int, array{value: string, label: string}>, config: array<string, mixed>}>
     */
    public array $questions = [];

    public function mount(?Survey $survey = null): void
    {
        $this->survey = $survey;

        Gate::authorize($this->survey?->exists ? 'update' : 'create', $this->survey ?? Survey::class);

        if (! $this->survey?->exists) {
            return;
        }

        $this->title = $this->survey->title;
        $this->description = (string) $this->survey->description;
        $this->scope = $this->survey->scope->value;
        $this->aid_program_id = $this->survey->aid_program_id;
        $this->is_active = $this->survey->is_active;
        $this->is_required = $this->survey->is_required;
        $this->starts_at = $this->survey->starts_at?->format('Y-m-d\TH:i');
        $this->ends_at = $this->survey->ends_at?->format('Y-m-d\TH:i');

        $this->questions = $this->survey->questions->map(fn (SurveyQuestion $question): array => [
            'id' => $question->id,
            'type' => $question->type->value,
            'label' => $question->label,
            'help_text' => (string) $question->help_text,
            'is_required' => $question->is_required,
            'options' => $question->options ?? [],
            'config' => $question->config ?? [],
        ])->all();
    }

    /**
     * Active aid programs, for the "program-specific" scope's program
     * select.
     *
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
     * @return array<int, SurveyQuestionType>
     */
    #[Computed]
    public function questionTypes(): array
    {
        return SurveyQuestionType::cases();
    }

    /**
     * @return array<int, SurveyScope>
     */
    #[Computed]
    public function scopes(): array
    {
        return SurveyScope::cases();
    }

    public function addQuestion(string $type): void
    {
        $questionType = SurveyQuestionType::from($type);

        $this->questions[] = [
            'type' => $questionType->value,
            'label' => '',
            'help_text' => '',
            'is_required' => false,
            'options' => $questionType->hasOptions() ? [
                ['value' => 'option_1', 'label' => ''],
                ['value' => 'option_2', 'label' => ''],
            ] : [],
            'config' => $questionType === SurveyQuestionType::Rating ? ['max_stars' => 5] : [],
        ];
    }

    public function removeQuestion(int $index): void
    {
        unset($this->questions[$index]);

        $this->questions = array_values($this->questions);
    }

    public function moveUp(int $index): void
    {
        if ($index <= 0) {
            return;
        }

        $this->swapQuestions($index, $index - 1);
    }

    public function moveDown(int $index): void
    {
        if ($index >= count($this->questions) - 1) {
            return;
        }

        $this->swapQuestions($index, $index + 1);
    }

    private function swapQuestions(int $a, int $b): void
    {
        [$this->questions[$a], $this->questions[$b]] = [$this->questions[$b], $this->questions[$a]];
    }

    public function addOption(int $index): void
    {
        $count = count($this->questions[$index]['options'] ?? []);

        $this->questions[$index]['options'][] = [
            'value' => 'option_'.($count + 1),
            'label' => '',
        ];
    }

    public function removeOption(int $index, int $optionIndex): void
    {
        unset($this->questions[$index]['options'][$optionIndex]);

        $this->questions[$index]['options'] = array_values($this->questions[$index]['options']);
    }

    public function save(): void
    {
        $isUpdate = $this->survey?->exists ?? false;

        Gate::authorize($isUpdate ? 'update' : 'create', $this->survey ?? Survey::class);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'scope' => ['required', Rule::enum(SurveyScope::class)],
            'aid_program_id' => [
                Rule::requiredIf($this->scope === SurveyScope::Program->value),
                'nullable', 'integer',
                Rule::exists('aid_programs', 'id')->whereNull('deleted_at'),
            ],
            'is_active' => ['boolean'],
            'is_required' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.type' => ['required', Rule::enum(SurveyQuestionType::class)],
            'questions.*.label' => ['required', 'string', 'max:255'],
            'questions.*.help_text' => ['nullable', 'string'],
            'questions.*.is_required' => ['boolean'],
            'questions.*.options' => ['array'],
            'questions.*.options.*.value' => ['required', 'string', 'max:255'],
            'questions.*.options.*.label' => ['required', 'string', 'max:255'],
        ]);

        foreach ($this->questions as $index => $question) {
            if (SurveyQuestionType::from($question['type'])->hasOptions() && count($question['options'] ?? []) < 2) {
                $this->addError("questions.{$index}.options", __('surveys.builder.min_options'));

                return;
            }
        }

        try {
            $survey = app(SaveSurvey::class)->handle(
                $this->survey,
                [
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'scope' => $validated['scope'],
                    'aid_program_id' => $validated['aid_program_id'] ?? null,
                    'is_active' => $validated['is_active'] ?? false,
                    'is_required' => $validated['is_required'] ?? false,
                    'starts_at' => $validated['starts_at'] ?? null,
                    'ends_at' => $validated['ends_at'] ?? null,
                ],
                $this->questions,
                Auth::user(),
            );
        } catch (SurveyQuestionHasAnswersException|SurveyScopeRequiresProgramException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());

            return;
        }

        $this->dispatch('toast', type: 'success', message: __('surveys.messages.saved'));

        $this->redirectRoute('admin.surveys.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.surveys.builder');
    }
}
