<?php

namespace App\Console\Commands;

use App\Enums\AidStatus;
use App\Enums\ApprovalStageType;
use App\Listeners\NotifyStageApprovers;
use App\Models\Aid;
use App\Models\ApprovalDecision;
use App\Models\ApprovalFlowStage;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Daily SLA sweep: for every aid sitting at an approval stage longer than
 * that stage's configured max_days, alert the stage's approvers via the
 * in-app bell (the same recipient resolution + notifications mechanism the
 * {@see NotifyStageApprovers} listener uses on stage entry).
 *
 * Escalation is idempotent per stage entry: aids.approval_escalated_at
 * records the last escalation, and an aid is only re-escalated once the
 * marker is stale — i.e. it predates the timestamp at which the aid entered
 * its current stage — so advancing to a new stage re-arms escalation while a
 * stalled stage is not re-notified every day.
 */
class EscalateOverdueApprovals extends Command
{
    protected $signature = 'aids:escalate-overdue';

    protected $description = 'Escalate aids overdue at their current approval stage to that stage\'s approvers';

    public function handle(): int
    {
        $now = Carbon::now();
        $escalated = 0;

        $aids = Aid::query()
            ->where('status', AidStatus::UnderReview->value)
            ->whereHas('currentStage', function ($query): void {
                $query
                    ->whereNotNull('max_days')
                    // A beneficiary_response stage waits on the beneficiary,
                    // not staff: it never escalates to approvers.
                    ->where('type', '!=', ApprovalStageType::BeneficiaryResponse->value);
            })
            ->with(['currentStage', 'program', 'beneficiary'])
            ->get();

        foreach ($aids as $aid) {
            $stage = $aid->currentStage;

            if ($stage === null || $stage->max_days === null || $stage->isBeneficiaryResponse()) {
                continue;
            }

            $stageEntry = $this->stageEnteredAt($aid);

            if ($stageEntry === null) {
                continue;
            }

            // Overdue = the aid has been at this stage longer than the SLA
            // (its entry timestamp is older than max_days ago).
            if ($now->lessThanOrEqualTo($stageEntry->copy()->addDays($stage->max_days))) {
                continue;
            }

            // Escalate once per stage entry. A marker at/after the current
            // stage entry means we have already escalated this stage; a
            // marker predating it is stale (a new stage re-arms escalation).
            $lastEscalated = $aid->approval_escalated_at !== null
                ? Carbon::parse($aid->approval_escalated_at)
                : null;

            if ($lastEscalated !== null && $lastEscalated->greaterThanOrEqualTo($stageEntry)) {
                continue;
            }

            $days = (int) floor($stageEntry->diffInDays($now));

            $this->notifyApprovers($aid, $stage, $days);

            // forceFill: approval_escalated_at is intentionally not in Aid's
            // fillable/cast set; store a portable datetime string.
            $aid->forceFill(['approval_escalated_at' => $now->toDateTimeString()])->save();

            $escalated++;
        }

        $this->info("Escalated {$escalated} overdue approval(s).");

        return self::SUCCESS;
    }

    /**
     * When the aid entered its current stage: the timestamp of its most
     * recent approval decision (which advanced it here), or — if none has
     * been taken yet — its submission time.
     */
    private function stageEnteredAt(Aid $aid): ?Carbon
    {
        $latestDecision = ApprovalDecision::query()
            ->where('aid_id', $aid->id)
            ->max('decided_at');

        if ($latestDecision !== null) {
            return Carbon::parse($latestDecision);
        }

        return $aid->submitted_at;
    }

    /**
     * Alert the stage's approvers via the in-app bell, reusing the stage's
     * own eligible-user resolution (role holders unioned with named
     * assignees). The message is pre-rendered so the always-fixed bell
     * placeholder set still shows the day count.
     */
    private function notifyApprovers(Aid $aid, ApprovalFlowStage $stage, int $days): void
    {
        $recipients = $stage->eligibleUsers();

        if ($recipients->isEmpty()) {
            return;
        }

        $message = __('approvals.escalation.bell', [
            'reference' => (string) $aid->reference,
            'stage' => (string) $stage->name,
            'days' => $days,
        ]);

        foreach ($recipients as $recipient) {
            $recipient->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => 'approvals.escalation',
                'data' => [
                    'lang_key' => $message,
                    'aid_id' => $aid->id,
                    'reference' => $aid->reference,
                    'stage_name' => $stage->name,
                    'days' => $days,
                    'url' => route('aids.show', $aid),
                ],
            ]);
        }
    }
}
