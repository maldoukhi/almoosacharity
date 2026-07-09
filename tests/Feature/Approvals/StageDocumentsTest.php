<?php

use App\Actions\Approvals\RecordApprovalDecision;
use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Enums\ApprovalAction;
use App\Enums\ApprovalStageType;
use App\Livewire\Aids\ApprovalDecisionModal;
use App\Livewire\Settings\ApprovalFlows\Form;
use App\Models\Aid;
use App\Models\AidProgram;
use App\Models\ApprovalDecision;
use App\Models\ApprovalFlow;
use App\Models\Beneficiary;
use App\Models\User;
use Database\Factories\AidFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
});

/**
 * Builds an under_review cash aid pinned at stage 1 of the default flow,
 * with that stage's documents_required flag and required document types set
 * as requested. When $requiredDocuments is null a single legacy plain-string
 * document type is used (so the legacy shape is exercised end-to-end).
 *
 * @param  array<int, mixed>|null  $requiredDocuments
 */
function docStageAid(bool $documentsRequired, ?array $requiredDocuments = null): Aid
{
    seedAidCatalog();

    $flow = ApprovalFlow::query()->default()->where('is_active', true)->firstOrFail();
    $stage = $flow->stages()->where('order', 1)->firstOrFail();
    $stage->update([
        'documents_required' => $documentsRequired,
        'required_documents' => $documentsRequired
            ? ($requiredDocuments ?? ['صورة إثبات التسليم'])
            : null,
    ]);

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();

    return AidFactory::new()->create([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'status' => AidStatus::UnderReview,
        'approval_flow_id' => $flow->id,
        'current_stage_id' => $stage->id,
        'amount' => 1500,
        'submitted_at' => now(),
        'decided_at' => null,
    ]);
}

it('round-trips mixed mandatory/optional document types through the builder', function () {
    asAdmin();
    $approver = User::factory()->create();

    Livewire::test(Form::class)
        ->set('name', 'مسار المستندات')
        ->set('stages', [[
            'name' => 'رفع مستند',
            'order' => 1,
            'role' => '',
            'assignee_user_ids' => [$approver->id],
            'allowed_actions' => ['approve', 'reject'],
            'type' => ApprovalStageType::Approval->value,
            'documents_required' => true,
            'required_documents' => [
                ['label' => 'صورة الهوية', 'required' => true],
                ['label' => '  ', 'required' => true],
                ['label' => 'إثبات دخل', 'required' => false],
            ],
            'notify_channels' => ['in_app', 'whatsapp'],
        ]])
        ->call('save')
        ->assertHasNoErrors();

    $stage = ApprovalFlow::query()->where('name', 'مسار المستندات')->firstOrFail()->stages()->firstOrFail();

    expect($stage->type)->toBe(ApprovalStageType::Approval)
        ->and($stage->documents_required)->toBeTrue()
        // blank labels are trimmed away; label + required persist per type
        ->and($stage->required_documents)->toBe([
            ['label' => 'صورة الهوية', 'required' => true],
            ['label' => 'إثبات دخل', 'required' => false],
        ])
        ->and($stage->notify_channels)->toBe(['in_app', 'whatsapp']);
});

it('loads a legacy plain-string required_documents and re-saves it as required:true objects', function () {
    asAdmin();

    seedAidCatalog();

    $flow = ApprovalFlow::query()->default()->where('is_active', true)->firstOrFail();
    $stage = $flow->stages()->where('order', 1)->firstOrFail();
    $stage->update([
        'documents_required' => true,
        'required_documents' => ['مستند قديم', 'مستند آخر'],
    ]);

    Livewire::test(Form::class, ['flow' => $flow])
        ->call('save')
        ->assertHasNoErrors();

    $reloaded = $flow->fresh()->stages()->where('order', 1)->firstOrFail();

    expect($reloaded->required_documents)->toBe([
        ['label' => 'مستند قديم', 'required' => true],
        ['label' => 'مستند آخر', 'required' => true],
    ]);
});

it('rejects a decision when a mandatory document slot is empty', function () {
    asResearcher(); // holds the default stage-1 role
    $aid = docStageAid(documentsRequired: true);

    Livewire::test(ApprovalDecisionModal::class, ['aid' => $aid, 'action' => 'approve'])
        ->call('confirm')
        ->assertHasErrors('typedDocuments.0');

    expect($aid->fresh()->status)->toBe(AidStatus::UnderReview)
        ->and($aid->fresh()->current_stage_id)->toBe($aid->current_stage_id)
        ->and(ApprovalDecision::query()->where('aid_id', $aid->id)->count())->toBe(0);
});

it('accepts a decision when the mandatory slot is filled and tags the file with its label', function () {
    asResearcher();
    $aid = docStageAid(documentsRequired: true);

    Livewire::test(ApprovalDecisionModal::class, ['aid' => $aid, 'action' => 'approve'])
        ->set('typedDocuments.0', UploadedFile::fake()->create('proof.pdf', 120, 'application/pdf'))
        ->call('confirm')
        ->assertHasNoErrors();

    expect($aid->fresh()->status)->toBe(AidStatus::UnderReview)
        ->and($aid->fresh()->current_stage_id)->not->toBe($aid->current_stage_id);

    $decision = ApprovalDecision::query()->where('aid_id', $aid->id)->latest('id')->firstOrFail();
    $media = $decision->getMedia('decision_documents');

    expect($media)->toHaveCount(1)
        ->and($media->first()->disk)->toBe('local')
        ->and($media->first()->getCustomProperty('document_label'))->toBe('صورة إثبات التسليم');
});

it('lets an optional slot stay empty while the mandatory one is filled', function () {
    asResearcher();
    $aid = docStageAid(documentsRequired: true, requiredDocuments: [
        ['label' => 'الهوية', 'required' => true],
        ['label' => 'كشف حساب', 'required' => false],
    ]);

    Livewire::test(ApprovalDecisionModal::class, ['aid' => $aid, 'action' => 'approve'])
        ->set('typedDocuments.0', UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'))
        ->call('confirm')
        ->assertHasNoErrors();

    $decision = ApprovalDecision::query()->where('aid_id', $aid->id)->latest('id')->firstOrFail();
    $media = $decision->getMedia('decision_documents');

    expect($media)->toHaveCount(1)
        ->and($media->first()->getCustomProperty('document_label'))->toBe('الهوية');
});

it('rejects a mixed stage when the mandatory slot is empty even if the optional one is filled', function () {
    asResearcher();
    $aid = docStageAid(documentsRequired: true, requiredDocuments: [
        ['label' => 'الهوية', 'required' => true],
        ['label' => 'كشف حساب', 'required' => false],
    ]);

    Livewire::test(ApprovalDecisionModal::class, ['aid' => $aid, 'action' => 'approve'])
        ->set('typedDocuments.1', UploadedFile::fake()->create('statement.pdf', 100, 'application/pdf'))
        ->call('confirm')
        ->assertHasErrors('typedDocuments.0');

    expect(ApprovalDecision::query()->where('aid_id', $aid->id)->count())->toBe(0);
});

it('throws when the action is invoked directly without documents on a required stage', function () {
    $actor = asResearcher();
    $aid = docStageAid(documentsRequired: true);

    expect(fn () => app(RecordApprovalDecision::class)->handle($aid, $actor, ApprovalAction::Approve))
        ->toThrow(InvalidArgumentException::class);

    expect(ApprovalDecision::query()->where('aid_id', $aid->id)->count())->toBe(0);
});

it('allows an optional upload on a stage that does not require documents', function () {
    asResearcher();
    $aid = docStageAid(documentsRequired: false);

    Livewire::test(ApprovalDecisionModal::class, ['aid' => $aid, 'action' => 'approve'])
        ->set('documents', [UploadedFile::fake()->image('photo.jpg')])
        ->call('confirm')
        ->assertHasNoErrors();

    $decision = ApprovalDecision::query()->where('aid_id', $aid->id)->latest('id')->firstOrFail();

    expect($decision->getMedia('decision_documents'))->toHaveCount(1);
});

it('accepts a documents-not-required decision with no files at all', function () {
    asResearcher();
    $aid = docStageAid(documentsRequired: false);

    Livewire::test(ApprovalDecisionModal::class, ['aid' => $aid, 'action' => 'approve'])
        ->set('documents', [])
        ->call('confirm')
        ->assertHasNoErrors();

    $decision = ApprovalDecision::query()->where('aid_id', $aid->id)->latest('id')->firstOrFail();

    expect($decision->getMedia('decision_documents'))->toHaveCount(0);
});
