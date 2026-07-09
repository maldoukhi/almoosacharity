<?php

namespace App\Actions\BeneficiaryFlows;

use App\Enums\ApprovalAction;
use App\Enums\BeneficiaryStatus;
use App\Enums\RoleName;
use App\Models\BeneficiaryFlow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SaveBeneficiaryFlow
{
    /**
     * Create or update a beneficiary flow together with its ordered stages,
     * atomically. Stages are always fully replaced (delete then recreate)
     * and renumbered sequentially from 1 based on their position in the
     * given array — client-supplied order values are never trusted.
     *
     * @param  array{name: string, is_default?: bool, is_active?: bool, description?: ?string, stages: array<int, array{name: string, role?: ?string, assignee_user_ids?: array<int, int>, allowed_actions: array<int, string>}>}  $data
     *
     * @throws InvalidArgumentException
     */
    public function handle(array $data, ?BeneficiaryFlow $flow = null): BeneficiaryFlow
    {
        $stages = $data['stages'] ?? [];

        $this->assertValidStages($stages);

        if ($flow) {
            $this->assertNoBeneficiariesCurrentlyUnderReview($flow);
        }

        return DB::transaction(function () use ($data, $flow, $stages): BeneficiaryFlow {
            if (($data['is_default'] ?? false) === true) {
                BeneficiaryFlow::query()
                    ->when($flow, fn ($query) => $query->whereKeyNot($flow->id))
                    ->update(['is_default' => false]);
            }

            $attributes = [
                'name' => $data['name'],
                'is_default' => $data['is_default'] ?? false,
                'is_active' => $data['is_active'] ?? true,
                'description' => $data['description'] ?? null,
            ];

            if ($flow) {
                $flow->update($attributes);
            } else {
                $attributes['slug'] = $this->generateUniqueSlug($data['name']);
                $flow = BeneficiaryFlow::create($attributes);
            }

            $flow->stages()->delete();

            foreach ($stages as $index => $stageData) {
                $assigneeIds = array_values(array_unique(array_map('intval', $stageData['assignee_user_ids'] ?? [])));

                $flow->stages()->create([
                    'name' => $stageData['name'],
                    'order' => $index + 1,
                    'role' => ($stageData['role'] ?? '') !== '' ? $stageData['role'] : null,
                    'assignee_user_ids' => $assigneeIds !== [] ? $assigneeIds : null,
                    'allowed_actions' => array_values($stageData['allowed_actions']),
                ]);
            }

            return $flow->fresh('stages');
        });
    }

    /**
     * @param  array<int, array{name: string, role?: ?string, assignee_user_ids?: array<int, int>, allowed_actions: array<int, string>}>  $stages
     *
     * @throws InvalidArgumentException
     */
    private function assertValidStages(array $stages): void
    {
        if (count($stages) < 1) {
            throw new InvalidArgumentException(__('beneficiaries.flow.errors.at_least_one_stage'));
        }

        $validRoles = array_column(RoleName::cases(), 'value');
        $validActions = array_column(ApprovalAction::cases(), 'value');

        foreach ($stages as $stage) {
            $hasRole = ! empty($stage['role']) && in_array($stage['role'], $validRoles, true);
            $hasUsers = ! empty($stage['assignee_user_ids']);

            // A stage must target a valid role, at least one specific user, or
            // both — otherwise no one could ever act on it.
            if (! $hasRole && ! $hasUsers) {
                throw new InvalidArgumentException(__('beneficiaries.flow.errors.stage_assignee_required'));
            }

            if (! empty($stage['role']) && ! $hasRole) {
                throw new InvalidArgumentException(__('beneficiaries.flow.errors.stage_assignee_required'));
            }

            if (empty($stage['allowed_actions']) || count(array_intersect($stage['allowed_actions'], $validActions)) < 1) {
                throw new InvalidArgumentException(__('beneficiaries.flow.errors.stage_action_required'));
            }
        }
    }

    /**
     * Protect the flow's stage snapshots: refuse to rewrite the stages of a
     * flow that currently has beneficiaries under review, since those
     * beneficiaries' current_stage_id points at a stage row that a
     * delete-then-recreate would remove out from under them.
     *
     * @throws InvalidArgumentException
     */
    private function assertNoBeneficiariesCurrentlyUnderReview(BeneficiaryFlow $flow): void
    {
        if ($flow->beneficiaries()->where('status', BeneficiaryStatus::UnderReview->value)->exists()) {
            throw new InvalidArgumentException(__('beneficiaries.flow.errors.stages_in_use'));
        }
    }

    private function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base !== '' ? $base : 'flow';
        $attempt = 1;

        while (BeneficiaryFlow::where('slug', $slug)->exists()) {
            $attempt++;
            $slug = $base.'-'.$attempt;
        }

        return $slug;
    }
}
