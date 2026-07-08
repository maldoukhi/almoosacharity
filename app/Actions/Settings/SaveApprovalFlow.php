<?php

namespace App\Actions\Settings;

use App\Enums\AidStatus;
use App\Enums\ApprovalAction;
use App\Enums\RoleName;
use App\Models\ApprovalFlow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SaveApprovalFlow
{
    /**
     * Create or update an approval flow together with its ordered stages,
     * atomically. Stages are always fully replaced (delete then
     * recreate) and renumbered sequentially from 1 based on their
     * position in the given array — client-supplied order values are
     * never trusted.
     *
     * @param  array{name: string, is_default?: bool, is_active?: bool, description?: ?string, stages: array<int, array{name: string, role: string, allowed_actions: array<int, string>}>}  $data
     *
     * @throws InvalidArgumentException
     */
    public function handle(array $data, ?ApprovalFlow $flow = null): ApprovalFlow
    {
        $stages = $data['stages'] ?? [];

        $this->assertValidStages($stages);

        if ($flow) {
            $this->assertNoAidsCurrentlyUnderReview($flow);
        }

        return DB::transaction(function () use ($data, $flow, $stages): ApprovalFlow {
            if (($data['is_default'] ?? false) === true) {
                ApprovalFlow::query()
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
                $flow = ApprovalFlow::create($attributes);
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
            throw new InvalidArgumentException(__('validation.custom.approval_flow.at_least_one_stage'));
        }

        $validRoles = array_column(RoleName::cases(), 'value');
        $validActions = array_column(ApprovalAction::cases(), 'value');

        foreach ($stages as $stage) {
            $hasRole = ! empty($stage['role']) && in_array($stage['role'], $validRoles, true);
            $hasUsers = ! empty($stage['assignee_user_ids']);

            // A stage must target a valid role, at least one specific user,
            // or both — otherwise no one could ever act on it.
            if (! $hasRole && ! $hasUsers) {
                throw new InvalidArgumentException(__('validation.custom.approval_flow.stage_assignee_required'));
            }

            // If a role was supplied it must be a real one.
            if (! empty($stage['role']) && ! $hasRole) {
                throw new InvalidArgumentException(__('validation.custom.approval_flow.stage_assignee_required'));
            }

            if (empty($stage['allowed_actions']) || count(array_intersect($stage['allowed_actions'], $validActions)) < 1) {
                throw new InvalidArgumentException(__('validation.custom.approval_flow.stage_action_required'));
            }
        }
    }

    /**
     * Protect the flow's stage snapshots: refuse to rewrite the stages of
     * a flow that currently has aids under review, since those aids'
     * current_stage_id points at a stage row that a delete-then-recreate
     * would remove out from under them (nullifying it via the FK's
     * nullOnDelete and stalling the aid's progress).
     *
     * @throws InvalidArgumentException
     */
    private function assertNoAidsCurrentlyUnderReview(ApprovalFlow $flow): void
    {
        if ($flow->aids()->where('status', AidStatus::UnderReview->value)->exists()) {
            throw new InvalidArgumentException(__('validation.custom.approval_flow.stages_in_use'));
        }
    }

    private function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $attempt = 1;

        while (ApprovalFlow::where('slug', $slug)->exists()) {
            $attempt++;
            $slug = $base.'-'.$attempt;
        }

        return $slug;
    }
}
