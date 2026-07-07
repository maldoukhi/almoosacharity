<?php

namespace Database\Seeders;

use App\Enums\ApprovalAction;
use App\Enums\RoleName;
use App\Models\ApprovalFlow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ApprovalFlowSeeder extends Seeder
{
    /**
     * The two-stage default approval flow documented in CLAUDE.md:
     * social researcher study/recommendation, then manager final approval.
     */
    public function run(): void
    {
        $flow = ApprovalFlow::query()->firstOrCreate(
            ['slug' => Str::slug('المسار الافتراضي')],
            [
                'name' => 'المسار الافتراضي',
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
