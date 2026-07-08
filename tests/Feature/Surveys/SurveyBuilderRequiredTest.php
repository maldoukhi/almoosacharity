<?php

use App\Livewire\Surveys\Builder;
use App\Models\Survey;
use Livewire\Livewire;

it('persists is_required as true when the toggle is enabled', function () {
    asAdmin();

    Livewire::test(Builder::class)
        ->set('title', 'استبيان رضا المستفيدين')
        ->set('scope', 'general')
        ->set('is_required', true)
        ->call('addQuestion', 'short_text')
        ->set('questions.0.label', 'ما رأيك في الخدمة؟')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast', type: 'success');

    $survey = Survey::query()->where('title', 'استبيان رضا المستفيدين')->firstOrFail();

    expect($survey->is_required)->toBeTrue();
});

it('persists is_required as false when the toggle is disabled', function () {
    asAdmin();

    Livewire::test(Builder::class)
        ->set('title', 'استبيان اختياري')
        ->set('scope', 'general')
        ->set('is_required', false)
        ->call('addQuestion', 'short_text')
        ->set('questions.0.label', 'ملاحظاتك؟')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast', type: 'success');

    $survey = Survey::query()->where('title', 'استبيان اختياري')->firstOrFail();

    expect($survey->is_required)->toBeFalse();
});

it('updates an existing survey is_required flag and loads it back into the form', function () {
    $actor = asAdmin();

    $survey = Survey::factory()->create([
        'is_required' => false,
        'created_by' => $actor->id,
    ]);

    $component = Livewire::test(Builder::class, ['survey' => $survey]);

    // Loaded from the persisted survey (false at start).
    expect($component->get('is_required'))->toBeFalse();

    $component
        ->set('is_required', true)
        ->call('addQuestion', 'short_text')
        ->set('questions.0.label', 'سؤال جديد')
        ->call('save')
        ->assertHasNoErrors();

    expect($survey->fresh()->is_required)->toBeTrue();
});
