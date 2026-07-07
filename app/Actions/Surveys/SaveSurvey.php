<?php

namespace App\Actions\Surveys;

use App\Enums\SurveyScope;
use App\Exceptions\SurveyQuestionHasAnswersException;
use App\Exceptions\SurveyScopeRequiresProgramException;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SaveSurvey
{
    /**
     * Create or update a survey and rewrite its question list in one
     * transaction: questions carrying an existing id are updated in
     * place, questions without one are created, and any existing
     * question missing from $questions is deleted — unless it already
     * has recorded answers, in which case the whole save is rejected.
     *
     * @param  array{title: string, description?: ?string, scope: string, aid_program_id?: ?int, is_active?: bool, starts_at?: ?string, ends_at?: ?string}  $data
     * @param  array<int, array{id?: int, type: string, label: string, help_text?: ?string, is_required?: bool, options?: array<int, array{value: string, label: string}>, config?: array<string, mixed>}>  $questions
     *
     * @throws SurveyQuestionHasAnswersException
     * @throws SurveyScopeRequiresProgramException
     */
    public function handle(?Survey $survey, array $data, array $questions, User $actor): Survey
    {
        if ($data['scope'] === SurveyScope::Program->value && empty($data['aid_program_id'])) {
            throw SurveyScopeRequiresProgramException::make();
        }

        return DB::transaction(function () use ($survey, $data, $questions, $actor): Survey {
            $attributes = [
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'scope' => $data['scope'],
                'aid_program_id' => $data['scope'] === SurveyScope::Program->value ? $data['aid_program_id'] : null,
                'is_active' => $data['is_active'] ?? false,
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
            ];

            if ($survey?->exists) {
                $survey->update($attributes);
            } else {
                $survey = Survey::create($attributes + ['created_by' => $actor->id]);
            }

            $this->syncQuestions($survey, $questions);

            return $survey->fresh('questions');
        });
    }

    /**
     * @param  array<int, array{id?: int, type: string, label: string, help_text?: ?string, is_required?: bool, options?: array<int, array{value: string, label: string}>, config?: array<string, mixed>}>  $questions
     *
     * @throws SurveyQuestionHasAnswersException
     */
    private function syncQuestions(Survey $survey, array $questions): void
    {
        $existing = $survey->questions()->get();
        $keptIds = collect($questions)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();

        foreach ($existing as $question) {
            if (in_array($question->id, $keptIds, true)) {
                continue;
            }

            if ($question->answers()->exists()) {
                throw SurveyQuestionHasAnswersException::forQuestion($question->label);
            }

            $question->delete();
        }

        foreach (array_values($questions) as $index => $questionData) {
            $attributes = [
                'survey_id' => $survey->id,
                'type' => $questionData['type'],
                'label' => $questionData['label'],
                'help_text' => $questionData['help_text'] ?? null,
                'is_required' => $questionData['is_required'] ?? false,
                'position' => $index + 1,
                'options' => $questionData['options'] ?? [],
                'config' => $questionData['config'] ?? [],
            ];

            if (! empty($questionData['id'])) {
                SurveyQuestion::where('id', $questionData['id'])
                    ->where('survey_id', $survey->id)
                    ->update($attributes);
            } else {
                SurveyQuestion::create($attributes);
            }
        }
    }
}
