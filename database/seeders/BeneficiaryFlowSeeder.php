<?php

namespace Database\Seeders;

use App\Enums\ApprovalAction;
use App\Enums\RoleName;
use App\Models\BeneficiaryFlow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BeneficiaryFlowSeeder extends Seeder
{
    /**
     * The default two-stage beneficiary review flow: social researcher
     * study/recommendation, then manager final approval — mirroring the aid
     * approval flow documented in CLAUDE.md.
     */
    public function run(): void
    {
        $flow = BeneficiaryFlow::query()->firstOrCreate(
            ['slug' => Str::slug('مسار قبول المستفيدين') ?: 'beneficiary-default-flow'],
            [
                'name' => 'مسار قبول المستفيدين',
                'is_default' => true,
                'is_active' => true,
            ],
        );

        if ($flow->stages()->count() > 0) {
            return;
        }

        $allActions = [
            ApprovalAction::Approve->value,
            ApprovalAction::Reject->value,
            ApprovalAction::Return->value,
        ];

        $flow->stages()->create([
            'name' => 'الدراسة الاجتماعية',
            'order' => 1,
            'role' => RoleName::SocialResearcher->value,
            'allowed_actions' => $allActions,
        ]);

        $flow->stages()->create([
            'name' => 'الاعتماد النهائي',
            'order' => 2,
            'role' => RoleName::Manager->value,
            'allowed_actions' => $allActions,
        ]);
    }
}
