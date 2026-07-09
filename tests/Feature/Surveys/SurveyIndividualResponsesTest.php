<?php

use App\Enums\AidProgramType;
use App\Enums\SurveyQuestionType;
use App\Livewire\Surveys\Results;
use App\Models\Aid;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Livewire\Livewire;

/**
 * Build a survey with one question of each answered type and return it with
 * its questions keyed by kind for convenient answering.
 *
 * @return array{survey: Survey, text: SurveyQuestion, choice: SurveyQuestion, rating: SurveyQuestion, yesno: SurveyQuestion}
 */
function surveyWithEveryQuestionType(): array
{
    $survey = Survey::factory()->create();

    $text = SurveyQuestion::factory()->create([
        'survey_id' => $survey->id,
        'type' => SurveyQuestionType::ShortText,
        'label' => 'ما رأيك في الخدمة؟',
        'position' => 1,
    ]);

    $choice = SurveyQuestion::factory()->singleChoice()->create([
        'survey_id' => $survey->id,
        'label' => 'كيف تقيّم التعامل؟',
        'position' => 2,
    ]);

    $rating = SurveyQuestion::factory()->rating()->create([
        'survey_id' => $survey->id,
        'label' => 'قيّم تجربتك',
        'position' => 3,
    ]);

    $yesno = SurveyQuestion::factory()->yesNo()->create([
        'survey_id' => $survey->id,
        'label' => 'هل وصلتك الإعانة؟',
        'position' => 4,
    ]);

    return compact('survey', 'text', 'choice', 'rating', 'yesno');
}

it('lists the survey responses with respondent name and related aid in the individual view', function () {
    asAdmin();
    seedAidCatalog();

    ['survey' => $survey] = surveyWithEveryQuestionType();

    $beneficiary = Beneficiary::factory()->create([
        'first_name' => 'محمد',
        'last_name' => 'العتيبي',
    ]);

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $aid = Aid::factory()->create([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
    ]);

    SurveyResponse::create([
        'survey_id' => $survey->id,
        'beneficiary_id' => $beneficiary->id,
        'aid_id' => $aid->id,
        'submitted_at' => now(),
        'ip' => '127.0.0.1',
    ]);

    Livewire::test(Results::class, ['survey' => $survey])
        ->call('switchView', 'individual')
        ->assertSee('محمد العتيبي')
        ->assertSee($aid->reference);
});

it('reveals a response full per-question answers when expanded', function () {
    asAdmin();

    ['survey' => $survey, 'text' => $text, 'choice' => $choice, 'rating' => $rating, 'yesno' => $yesno]
        = surveyWithEveryQuestionType();

    $response = SurveyResponse::create([
        'survey_id' => $survey->id,
        'submitted_at' => now(),
        'ip' => '127.0.0.1',
    ]);

    $response->answers()->create(['survey_question_id' => $text->id, 'value' => 'خدمة ممتازة وسريعة']);
    $response->answers()->create(['survey_question_id' => $choice->id, 'value' => 'excellent']);
    $response->answers()->create(['survey_question_id' => $rating->id, 'value' => 4]);
    $response->answers()->create(['survey_question_id' => $yesno->id, 'value' => true]);

    Livewire::test(Results::class, ['survey' => $survey])
        ->call('switchView', 'individual')
        ->call('toggleResponse', $response->id)
        // text answer verbatim
        ->assertSee('خدمة ممتازة وسريعة')
        // single-choice resolved to its human label, not the raw value
        ->assertSee('ممتاز')
        ->assertDontSee('excellent')
        // rating rendered as "4/5"
        ->assertSee('4/5')
        // yes-no rendered as the localized "yes"
        ->assertSee(__('surveys.aid_detail.yes'));
});

it('never shows a response that belongs to another survey', function () {
    asAdmin();

    ['survey' => $survey, 'text' => $text] = surveyWithEveryQuestionType();

    // A response + answer on a DIFFERENT survey must never leak in.
    $otherSurvey = Survey::factory()->create();
    $otherQuestion = SurveyQuestion::factory()->create([
        'survey_id' => $otherSurvey->id,
        'type' => SurveyQuestionType::ShortText,
        'label' => 'سؤال استبيان آخر',
    ]);
    $otherResponse = SurveyResponse::create([
        'survey_id' => $otherSurvey->id,
        'submitted_at' => now(),
        'ip' => '127.0.0.1',
    ]);
    $otherResponse->answers()->create([
        'survey_question_id' => $otherQuestion->id,
        'value' => 'إجابة من استبيان آخر',
    ]);

    // A legitimate response on OUR survey.
    $ownResponse = SurveyResponse::create([
        'survey_id' => $survey->id,
        'submitted_at' => now(),
        'ip' => '127.0.0.1',
    ]);
    $ownResponse->answers()->create([
        'survey_question_id' => $text->id,
        'value' => 'إجابة صحيحة',
    ]);

    $component = Livewire::test(Results::class, ['survey' => $survey])
        ->call('switchView', 'individual');

    // Only this survey's response is listed and expandable.
    expect($component->instance()->responses->total())->toBe(1);

    $component->call('toggleResponse', $ownResponse->id)
        ->assertSee('إجابة صحيحة')
        ->assertDontSee('إجابة من استبيان آخر');

    // Attempting to expand a foreign response id yields no detail.
    $component->call('toggleResponse', $otherResponse->id)
        ->assertDontSee('إجابة من استبيان آخر');
});

it('shows a friendly empty state when the survey has no responses', function () {
    asAdmin();

    ['survey' => $survey] = surveyWithEveryQuestionType();

    Livewire::test(Results::class, ['survey' => $survey])
        ->call('switchView', 'individual')
        ->assertSee(__('surveys.results.no_responses_title'))
        ->assertSee(__('surveys.results.no_responses_description'));
});
