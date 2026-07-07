<?php

namespace App\Actions\Surveys;

use App\Enums\SurveyQuestionType;
use App\Models\Aid;
use App\Models\AidConfirmation;
use App\Models\Beneficiary;
use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RecordSurveyResponse
{
    /**
     * Validate every answer against its question's own validation rules
     * (see {@see SurveyQuestionType::validationRules()}), then
     * persist the response and one answer row per question in a single
     * transaction. Throws the normal Laravel ValidationException on the
     * first invalid answer.
     *
     * @param  array<int, mixed>  $answers  keyed by survey_question_id
     */
    public function handle(
        Survey $survey,
        array $answers,
        ?Aid $aid,
        ?Beneficiary $beneficiary,
        ?string $ip,
        ?AidConfirmation $aidConfirmation = null,
    ): SurveyResponse {
        $questions = $survey->questions()->orderBy('position')->get();

        $validatedValues = [];

        foreach ($questions as $question) {
            $config = array_merge($question->config ?? [], ['options' => $question->options ?? []]);
            $rules = $question->type->validationRules($question->is_required, $config);

            $validator = Validator::make(
                ['value' => $answers[$question->id] ?? null],
                $rules,
                [],
                ['value' => $question->label],
            );

            $validator->validate();

            $validatedValues[$question->id] = $validator->validated()['value'] ?? null;
        }

        return DB::transaction(function () use ($survey, $questions, $validatedValues, $aid, $beneficiary, $ip, $aidConfirmation): SurveyResponse {
            $response = SurveyResponse::create([
                'survey_id' => $survey->id,
                'aid_id' => $aid?->id,
                'beneficiary_id' => $beneficiary?->id,
                'aid_confirmation_id' => $aidConfirmation?->id,
                'submitted_at' => now(),
                'ip' => $ip,
            ]);

            foreach ($questions as $question) {
                $response->answers()->create([
                    'survey_question_id' => $question->id,
                    'value' => $validatedValues[$question->id] ?? null,
                ]);
            }

            return $response->fresh('answers');
        });
    }
}
