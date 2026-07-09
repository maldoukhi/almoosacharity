<?php

use App\Enums\SurveyQuestionType;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Reports\Filters\SurveysFilter;
use App\Reports\SurveysReport;

it('aggregates rating and choice questions with correct averages, counts and percentages', function () {
    $survey = Survey::factory()->create();

    $rating = SurveyQuestion::factory()->rating()->for($survey)->create(['position' => 1]);
    $choice = SurveyQuestion::factory()->singleChoice()->for($survey)->create(['position' => 2]);

    // Three responses: ratings 5/4/3 (avg 4.0), choices excellent/excellent/good.
    $fixtures = [
        [5, 'excellent'],
        [4, 'excellent'],
        [3, 'good'],
    ];

    foreach ($fixtures as [$star, $option]) {
        $response = SurveyResponse::factory()->for($survey)->create();

        SurveyAnswer::factory()->create([
            'survey_response_id' => $response->id,
            'survey_question_id' => $rating->id,
            'value' => $star,
        ]);

        SurveyAnswer::factory()->create([
            'survey_response_id' => $response->id,
            'survey_question_id' => $choice->id,
            'value' => $option,
        ]);
    }

    $report = new SurveysReport(new SurveysFilter(surveyId: $survey->id));

    $summary = $report->surveys()->first();

    expect($summary['total_responses'])->toBe(3);

    // Rating: average 4.0, and the 5-star bucket holds one answer (33%).
    $ratingResult = collect($summary['questions'])->firstWhere('type', SurveyQuestionType::Rating);
    expect($ratingResult['average'])->toBe(4.0);
    expect($ratingResult['min'])->toBe(3);
    expect($ratingResult['max'])->toBe(5);

    $fiveStar = collect($ratingResult['distribution'])->firstWhere('star', 5);
    expect($fiveStar['count'])->toBe(1);
    expect($fiveStar['percentage'])->toBe(33);

    // Single choice: "excellent" chosen twice (67%), "good" once (33%), "poor" none.
    $choiceResult = collect($summary['questions'])->firstWhere('type', SurveyQuestionType::SingleChoice);
    $excellent = collect($choiceResult['options'])->firstWhere('label', 'ممتاز');
    $good = collect($choiceResult['options'])->firstWhere('label', 'جيد');
    $poor = collect($choiceResult['options'])->firstWhere('label', 'ضعيف');

    expect($excellent['count'])->toBe(2);
    expect($excellent['percentage'])->toBe(67);
    expect($good['count'])->toBe(1);
    expect($poor['count'])->toBe(0);

    // Totals reflect the one in-scope survey and its three responses.
    $totals = $report->totals();
    expect($totals['surveys'])->toBe(1);
    expect($totals['responses'])->toBe(3);
});

it('only includes the selected survey when a survey filter is set', function () {
    $target = Survey::factory()->create();
    $other = Survey::factory()->create();

    SurveyResponse::factory()->count(2)->for($target)->create();
    SurveyResponse::factory()->count(5)->for($other)->create();

    $report = new SurveysReport(new SurveysFilter(surveyId: $target->id));

    expect($report->surveys())->toHaveCount(1);
    expect($report->totals()['responses'])->toBe(2);
});
