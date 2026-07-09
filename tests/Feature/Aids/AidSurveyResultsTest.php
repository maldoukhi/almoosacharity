<?php

use App\Enums\AidProgramType;
use App\Enums\AidType;
use App\Enums\SurveyQuestionType;
use App\Enums\SurveyScope;
use App\Livewire\Aids\Show;
use App\Models\Aid;
use App\Models\AidConfirmation;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Database\Factories\AidFactory;
use Livewire\Livewire;

/**
 * Build a delivered aid + its confirmation link for the given (or a fresh)
 * beneficiary, seeding the aid catalog first so AidFactory never falls back
 * to a factory on an empty table.
 *
 * @return array{0: Aid, 1: AidConfirmation, 2: Beneficiary}
 */
function deliveredAidWithConfirmation(?Beneficiary $beneficiary = null): array
{
    seedAidCatalog();

    $beneficiary ??= Beneficiary::factory()->create();
    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();

    $aid = AidFactory::new()->delivered()->create([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 750,
    ]);

    $confirmation = AidConfirmation::factory()->confirmed()->create(['aid_id' => $aid->id]);

    return [$aid, $confirmation, $beneficiary];
}

/**
 * Attach a four-question survey (of every non-trivial type) to the given
 * confirmation as a completed response with concrete answers.
 */
function completedSurveyResponse(
    AidConfirmation $confirmation,
    Beneficiary $beneficiary,
    SurveyScope $scope = SurveyScope::General,
): SurveyResponse {
    $survey = Survey::factory()
        ->when($scope === SurveyScope::Program, fn ($f) => $f->program())
        ->create(['title' => 'استبيان رضا المستفيد']);

    $text = SurveyQuestion::factory()->create([
        'survey_id' => $survey->id,
        'type' => SurveyQuestionType::ShortText,
        'label' => 'ما رأيك بالخدمة؟',
        'position' => 1,
    ]);

    $choice = SurveyQuestion::factory()->singleChoice()->create([
        'survey_id' => $survey->id,
        'label' => 'كيف تقيّم سرعة الاستجابة؟',
        'position' => 2,
    ]);

    $rating = SurveyQuestion::factory()->rating()->create([
        'survey_id' => $survey->id,
        'label' => 'تقييمك العام',
        'position' => 3,
    ]);

    $yesNo = SurveyQuestion::factory()->yesNo()->create([
        'survey_id' => $survey->id,
        'label' => 'هل وصلتك الإعانة كاملة؟',
        'position' => 4,
    ]);

    $response = SurveyResponse::create([
        'survey_id' => $survey->id,
        'aid_id' => $confirmation->aid_id,
        'beneficiary_id' => $beneficiary->id,
        'aid_confirmation_id' => $confirmation->id,
        'submitted_at' => now(),
        'ip' => '127.0.0.1',
    ]);

    $response->answers()->create(['survey_question_id' => $text->id, 'value' => 'خدمة ممتازة وسريعة']);
    $response->answers()->create(['survey_question_id' => $choice->id, 'value' => 'excellent']);
    $response->answers()->create(['survey_question_id' => $rating->id, 'value' => 4]);
    $response->answers()->create(['survey_question_id' => $yesNo->id, 'value' => true]);

    return $response;
}

it('renders the beneficiary\'s own survey answers on the aid detail page', function () {
    asManager();
    [$aid, $confirmation, $beneficiary] = deliveredAidWithConfirmation();
    completedSurveyResponse($confirmation, $beneficiary);

    Livewire::test(Show::class, ['aid' => $aid])
        ->assertSee('استبيان رضا المستفيد')
        ->assertSee('ما رأيك بالخدمة؟')
        ->assertSee('خدمة ممتازة وسريعة')     // free-text answer
        ->assertSee('كيف تقيّم سرعة الاستجابة؟')
        ->assertSee('ممتاز')                    // resolved single-choice label
        ->assertSee('4/5')                      // rating value
        ->assertSee('هل وصلتك الإعانة كاملة؟')
        ->assertSee('نعم');                     // yes/no answer
});

it('resolves answers the same way for a program-scoped survey', function () {
    asManager();
    [$aid, $confirmation, $beneficiary] = deliveredAidWithConfirmation();
    completedSurveyResponse($confirmation, $beneficiary, SurveyScope::Program);

    Livewire::test(Show::class, ['aid' => $aid])
        ->assertSee('خدمة ممتازة وسريعة')
        ->assertSee('ممتاز')
        ->assertSee('4/5');
});

it('shows a friendly empty state when the aid has no survey response yet', function () {
    asManager();
    [$aid] = deliveredAidWithConfirmation();

    Livewire::test(Show::class, ['aid' => $aid])
        ->assertSee(__('surveys.aid_detail.empty_title'))
        ->assertSee(__('surveys.aid_detail.empty_description'))
        ->assertDontSee('استبيان رضا المستفيد');
});

it('never surfaces another aid\'s survey response', function () {
    asManager();

    // Aid A has a completed survey response.
    [, $confirmationA, $beneficiaryA] = deliveredAidWithConfirmation();
    completedSurveyResponse($confirmationA, $beneficiaryA);

    // Aid B (a different aid, its own confirmation, no response) must not
    // borrow aid A's answers.
    [$aidB] = deliveredAidWithConfirmation();

    Livewire::test(Show::class, ['aid' => $aidB])
        ->assertSee(__('surveys.aid_detail.empty_title'))
        ->assertDontSee('خدمة ممتازة وسريعة');
});
