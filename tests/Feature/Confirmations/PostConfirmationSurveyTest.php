<?php

use App\Actions\Confirmations\CreateAidConfirmation;
use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Enums\RoleName;
use App\Enums\SurveyQuestionType;
use App\Enums\SurveyScope;
use App\Livewire\Public\ConfirmReceipt;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Database\Factories\AidFactory;
use Livewire\Livewire;

function deliveredAidWithGeneralSurvey(): array
{
    seedAidCatalog();

    // BeneficiaryFactory resolves `created_by` from an existing user, and
    // surveys.created_by is itself NOT NULL, so one must exist first.
    $creator = userWithRole(RoleName::DataEntry);

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();

    $aid = AidFactory::new()->approved()->create([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 450,
        'status' => AidStatus::Delivered,
    ]);

    ['confirmation' => $confirmation, 'rawToken' => $rawToken] = app(CreateAidConfirmation::class)->handle($aid);

    // App\Models\Survey/SurveyQuestion do not `use HasFactory`, even
    // though Database\Factories\SurveyFactory/SurveyQuestionFactory exist
    // and are fully wired up (the same bug shape App\Models\Aid had before
    // it was fixed — see tests/Pest.php's AidFactory notes). Worse: it's
    // not just Survey::factory()/SurveyQuestion::factory() that throw —
    // SurveyQuestionFactory::definition() itself unconditionally contains
    // `'survey_id' => Survey::factory()`, so instantiating
    // Database\Factories\SurveyQuestionFactory directly *also* throws the
    // same "Call to undefined method Survey::factory()" even when
    // 'survey_id' is overridden, since the definition() array is fully
    // evaluated before overrides are merged. Both factories are therefore
    // entirely unusable as things stand; create the models directly
    // instead (both are fully mass-assignable via their #[Fillable(...)]
    // attribute, so no factory is actually required for this test's
    // purposes).
    $survey = Survey::query()->create([
        'title' => 'استبيان رضا المستفيدين',
        'is_active' => true,
        'scope' => SurveyScope::General,
        'created_by' => $creator->id,
    ]);

    $question = SurveyQuestion::query()->create([
        'survey_id' => $survey->id,
        'type' => SurveyQuestionType::ShortText,
        'label' => 'كيف تقيّم الخدمة؟',
        'is_required' => true,
        'position' => 1,
        'options' => [],
        'config' => [],
    ]);

    return [$aid, $confirmation, $rawToken, $survey, $question];
}

it('records a survey response linked to the aid_confirmation via the full public confirm -> survey flow', function () {
    [, $confirmation, $rawToken, , $question] = deliveredAidWithGeneralSurvey();

    Livewire::withQueryParams(['token' => $rawToken]);

    Livewire::test(ConfirmReceipt::class, ['confirmation' => $confirmation])
        ->call('confirm')
        ->assertSet('view', 'success')
        ->call('startSurvey')
        ->assertSet('view', 'survey')
        ->set("answers.{$question->id}", 'كانت الخدمة ممتازة')
        ->call('nextStep')
        ->assertSet('view', 'done');

    $response = SurveyResponse::query()->where('aid_confirmation_id', $confirmation->id)->first();

    expect($response)->not->toBeNull();

    // ->value('value') is a query-builder shortcut that bypasses Eloquent
    // casts (SurveyAnswer::value is cast to 'array'/JSON) and would return
    // the raw encoded column instead of the decoded string — hydrate the
    // model itself so the cast actually applies.
    $answer = SurveyAnswer::query()
        ->where('survey_response_id', $response->id)
        ->where('survey_question_id', $question->id)
        ->first();

    expect($answer->value)->toBe('كانت الخدمة ممتازة');
});

it('advances to the next question (currentQuestion is not stale after nextStep)', function () {
    [, $confirmation, $rawToken, $survey, $first] = deliveredAidWithGeneralSurvey();

    // A second question, so nextStep() must actually advance the step
    // rather than jumping straight to submit. This is the regression guard
    // for the bug where nextStep read the memoized currentQuestion before
    // incrementing step, leaving the screen stuck on the first question.
    $second = SurveyQuestion::query()->create([
        'survey_id' => $survey->id,
        'type' => SurveyQuestionType::ShortText,
        'label' => 'هل لديك أي ملاحظات؟',
        'is_required' => true,
        'position' => 2,
        'options' => [],
        'config' => [],
    ]);

    Livewire::withQueryParams(['token' => $rawToken]);

    Livewire::test(ConfirmReceipt::class, ['confirmation' => $confirmation])
        ->call('confirm')
        ->call('startSurvey')
        ->assertSet('step', 0)
        ->set("answers.{$first->id}", 'ممتاز')
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('view', 'survey')
        ->assertSet('step', 1)
        ->assertSee($second->label)
        ->set("answers.{$second->id}", 'لا شكرًا')
        ->call('nextStep')
        ->assertSet('view', 'done');
});

it('rejects submitting the survey when a required answer is missing', function () {
    [, $confirmation, $rawToken, , $question] = deliveredAidWithGeneralSurvey();

    Livewire::withQueryParams(['token' => $rawToken]);

    Livewire::test(ConfirmReceipt::class, ['confirmation' => $confirmation])
        ->call('confirm')
        ->call('startSurvey')
        ->set("answers.{$question->id}", '')
        ->call('nextStep')
        ->assertHasErrors(["answers.{$question->id}"]);

    expect(SurveyResponse::query()->where('aid_confirmation_id', $confirmation->id)->exists())->toBeFalse();
});
