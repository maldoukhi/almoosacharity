<?php

namespace Database\Seeders;

use App\Actions\Aids\SubmitAid;
use App\Actions\Approvals\RecordApprovalDecision;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Enums\ApprovalAction;
use App\Enums\IncomeSourceType;
use App\Enums\RelationKind;
use App\Enums\RoleName;
use App\Models\Aid;
use App\Models\AidItem;
use App\Models\AidProgram;
use App\Models\ApprovalFlow;
use App\Models\Beneficiary;
use App\Models\BeneficiaryCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class DemoDataSeeder extends Seeder
{
    /**
     * Create 25 diverse beneficiaries and 20 aids covering all approval states.
     * Only runs in local environment.
     */
    public function run(): void
    {
        // Only run in local environment
        if (app()->environment() !== 'local') {
            return;
        }

        $this->createBeneficiariesAndRelations();
        $this->createDemoAids();
    }

    private function createBeneficiariesAndRelations(): void
    {
        $dataEntryUser = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', RoleName::DataEntry->value))
            ->first();

        if (! $dataEntryUser) {
            $dataEntryUser = User::query()->first();
        }

        $categories = BeneficiaryCategory::all();

        // Create 25 beneficiaries with varying family structures
        for ($i = 0; $i < 25; $i++) {
            $beneficiary = Beneficiary::factory()
                ->create(['created_by' => $dataEntryUser->id]);

            // Assign 1-2 random categories
            $assignedCategories = $categories->random(rand(1, 2))->pluck('id');
            $beneficiary->categories()->attach($assignedCategories);

            // Create 0-6 family members (60% chance to have at least one)
            $familyMemberCount = rand(0, 100) < 60 ? rand(1, 6) : 0;
            for ($j = 0; $j < $familyMemberCount; $j++) {
                $this->createFamilyMemberFor($beneficiary);
            }

            // Create 0-3 income sources (70% chance to have at least one)
            $incomeSourceCount = rand(0, 100) < 70 ? rand(1, 3) : 0;
            for ($k = 0; $k < $incomeSourceCount; $k++) {
                $this->createIncomeSourceFor($beneficiary);
            }
        }
    }

    private function createFamilyMemberFor(Beneficiary $beneficiary): void
    {
        $maleNames = ['محمد', 'أحمد', 'سعود', 'فهد', 'خالد', 'علي', 'عبدالله'];
        $femaleNames = ['فاطمة', 'عائشة', 'علا', 'منى', 'حنان', 'نور', 'ريم'];
        $relations = RelationKind::cases();
        $relation = $relations[array_rand($relations)];

        $isMale = in_array($relation, [
            RelationKind::Son,
            RelationKind::Husband,
            RelationKind::Father,
            RelationKind::Brother,
        ]);

        $names = $isMale ? $maleNames : $femaleNames;

        $beneficiary->familyMembers()->create([
            'name' => $names[array_rand($names)].' '.$names[array_rand($names)],
            'relation' => $relation,
            'birth_date' => fake()->dateTimeBetween('-80 years', 'now'),
            'health_status' => rand(0, 100) < 60 ? 'سليم' : 'يعاني من مشاكل صحية',
            'education_status' => ['ابتدائي', 'متوسط', 'ثانوي', 'جامعي', 'أمي'][array_rand(['ابتدائي', 'متوسط', 'ثانوي', 'جامعي', 'أمي'])],
        ]);
    }

    private function createIncomeSourceFor(Beneficiary $beneficiary): void
    {
        $types = IncomeSourceType::cases();

        $beneficiary->incomeSources()->create([
            'source_type' => $types[array_rand($types)],
            'amount' => rand(100, 5000),
            'notes' => rand(0, 1) ? 'ملاحظات عن المصدر' : null,
        ]);
    }

    private function createDemoAids(): void
    {
        $dataEntryUser = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', RoleName::DataEntry->value))
            ->first() ?? User::query()->first();

        $researcherUser = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', RoleName::SocialResearcher->value))
            ->first();

        $managerUser = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', RoleName::Manager->value))
            ->first();

        $beneficiaries = Beneficiary::all();
        $programs = AidProgram::all();
        $flow = ApprovalFlow::query()->where('is_active', true)->first();

        if (! $flow || ! $beneficiaries->count() || ! $programs->count()) {
            return;
        }

        $submitAction = app(SubmitAid::class);
        $recordDecision = app(RecordApprovalDecision::class);

        // Create 20 aids with various statuses
        $statusDistribution = [
            'draft' => 3,
            'under_review_stage1' => 3,
            'under_review_stage2' => 3,
            'approved' => 4,
            'rejected' => 2,
            'returned' => 2,
            'cancelled' => 2,
            'delivered' => 1,
        ];

        $aidIndex = 0;
        foreach ($statusDistribution as $statusType => $count) {
            for ($i = 0; $i < $count; $i++) {
                $aidIndex++;
                $beneficiary = $beneficiaries->random();
                $program = $programs->random();

                // Ensure type matches program type
                $type = match ($program->type->value) {
                    'cash' => AidType::Cash,
                    'in_kind' => AidType::InKind,
                    'both' => rand(0, 1) ? AidType::Cash : AidType::InKind,
                };

                $reference = 'AID-'.now()->format('Y').'-'.str_pad((string) $aidIndex, 6, '0', STR_PAD_LEFT);

                $aid = Aid::create([
                    'reference' => $reference,
                    'beneficiary_id' => $beneficiary->id,
                    'aid_program_id' => $program->id,
                    'type' => $type,
                    'status' => AidStatus::Draft,
                    'amount' => $type === AidType::Cash ? rand(500, 5000) : null,
                    'purpose' => $this->generatePurpose(),
                    'notes' => rand(0, 1) ? 'ملاحظات حول الإعانة' : null,
                    'created_by' => $dataEntryUser->id,
                ]);

                // Add items for in-kind aids
                if ($type === AidType::InKind) {
                    $items = [
                        ['name' => 'سلة غذائية', 'quantity' => 1, 'value' => 500],
                        ['name' => 'بطانية', 'quantity' => 2, 'value' => 200],
                        ['name' => 'ملابس شتوية', 'quantity' => 1, 'value' => 300],
                        ['name' => 'ثلاجة', 'quantity' => 1, 'value' => 2000],
                        ['name' => 'مكيف هواء', 'quantity' => 1, 'value' => 2500],
                    ];

                    $itemCount = rand(1, 3);
                    for ($j = 0; $j < $itemCount; $j++) {
                        $item = $items[array_rand($items)];
                        AidItem::create([
                            'aid_id' => $aid->id,
                            'name' => $item['name'],
                            'quantity' => $item['quantity'],
                            'estimated_value' => $item['quantity'] * $item['value'],
                        ]);
                    }
                }

                // Apply status transitions
                $this->applyStatusTransition($aid, $statusType, $submitAction, $recordDecision, $researcherUser, $managerUser);
            }
        }
    }

    private function applyStatusTransition(
        Aid $aid,
        string $statusType,
        SubmitAid $submitAction,
        RecordApprovalDecision $recordDecision,
        ?User $researcherUser,
        ?User $managerUser,
    ): void {
        try {
            match ($statusType) {
                'draft' => null, // Leave as draft
                'under_review_stage1' => $this->submitToReview($aid, $submitAction),
                'under_review_stage2' => $this->submitAndApproveStage1($aid, $submitAction, $recordDecision, $researcherUser),
                'approved' => $this->approveAll($aid, $submitAction, $recordDecision, $researcherUser, $managerUser),
                'rejected' => $this->reject($aid, $submitAction, $recordDecision, $researcherUser),
                'returned' => $this->returnForRework($aid, $submitAction, $recordDecision, $researcherUser),
                'cancelled' => $this->cancel($aid),
                'delivered' => $this->deliver($aid, $submitAction, $recordDecision, $researcherUser, $managerUser),
                default => null,
            };
        } catch (\Exception $e) {
            // Log error but continue with seeding
            Log::warning("Could not apply status transition for aid {$aid->id}: ".$e->getMessage());
        }
    }

    private function submitToReview(Aid $aid, SubmitAid $submitAction): void
    {
        $submitAction->handle($aid, $aid->createdBy);
    }

    private function submitAndApproveStage1(
        Aid $aid,
        SubmitAid $submitAction,
        RecordApprovalDecision $recordDecision,
        ?User $researcherUser,
    ): void {
        $submitAction->handle($aid, $aid->createdBy);
        $aid->refresh();

        if ($researcherUser && $aid->status === AidStatus::UnderReview) {
            $recordDecision->handle($aid, $researcherUser, ApprovalAction::Approve);
        }
    }

    private function approveAll(
        Aid $aid,
        SubmitAid $submitAction,
        RecordApprovalDecision $recordDecision,
        ?User $researcherUser,
        ?User $managerUser,
    ): void {
        $submitAction->handle($aid, $aid->createdBy);
        $aid->refresh();

        if ($researcherUser && $aid->status === AidStatus::UnderReview) {
            $recordDecision->handle($aid, $researcherUser, ApprovalAction::Approve);
            $aid->refresh();
        }

        if ($managerUser && $aid->status === AidStatus::UnderReview && $aid->current_stage_id) {
            $recordDecision->handle($aid, $managerUser, ApprovalAction::Approve);
        }
    }

    private function reject(
        Aid $aid,
        SubmitAid $submitAction,
        RecordApprovalDecision $recordDecision,
        ?User $researcherUser,
    ): void {
        $submitAction->handle($aid, $aid->createdBy);
        $aid->refresh();

        if ($researcherUser && $aid->status === AidStatus::UnderReview) {
            $recordDecision->handle($aid, $researcherUser, ApprovalAction::Reject, 'لا تتوفر الشروط المطلوبة');
        }
    }

    private function returnForRework(
        Aid $aid,
        SubmitAid $submitAction,
        RecordApprovalDecision $recordDecision,
        ?User $researcherUser,
    ): void {
        $submitAction->handle($aid, $aid->createdBy);
        $aid->refresh();

        if ($researcherUser && $aid->status === AidStatus::UnderReview) {
            $recordDecision->handle($aid, $researcherUser, ApprovalAction::Return, 'يرجى تعديل البيانات والبيانات المالية');
        }
    }

    private function cancel(Aid $aid): void
    {
        // Set to cancelled status directly
        $aid->update([
            'status' => AidStatus::Cancelled,
        ]);
    }

    private function deliver(
        Aid $aid,
        SubmitAid $submitAction,
        RecordApprovalDecision $recordDecision,
        ?User $researcherUser,
        ?User $managerUser,
    ): void {
        $submitAction->handle($aid, $aid->createdBy);
        $aid->refresh();

        if ($researcherUser && $aid->status === AidStatus::UnderReview) {
            $recordDecision->handle($aid, $researcherUser, ApprovalAction::Approve);
            $aid->refresh();
        }

        if ($managerUser && $aid->status === AidStatus::UnderReview && $aid->current_stage_id) {
            $recordDecision->handle($aid, $managerUser, ApprovalAction::Approve);
            $aid->refresh();
        }

        // Move to delivered
        if ($aid->status === AidStatus::Approved) {
            $aid->update([
                'status' => AidStatus::InDisbursement,
            ]);
            $aid->update([
                'status' => AidStatus::Delivered,
                'decided_at' => now(),
            ]);
        }
    }

    private function generatePurpose(): string
    {
        $purposes = [
            'للمساعدة في تغطية نفقات المعيشة',
            'لسداد رسوم التعليم والدراسة',
            'لشراء احتياجات المنزل الأساسية',
            'للمساعدة في علاج طبي',
            'لترميم المنزل والصيانة',
            'للمساعدة في بدء مشروع صغير',
            'لسداد الإيجار والنفقات السكنية',
        ];

        return $purposes[array_rand($purposes)];
    }
}
