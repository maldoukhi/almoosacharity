<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SLA (service-level agreement) support for approval stages: an optional
 * per-stage maximum number of days an aid may sit at that stage before it
 * is considered overdue, plus a per-aid marker of the last time the aid's
 * current stage was escalated to its approvers. Both are nullable so the
 * existing behaviour is unchanged until an admin sets an SLA on a stage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_flow_stages', function (Blueprint $table): void {
            $table->unsignedSmallInteger('max_days')->nullable()->after('notify_channels');
        });

        Schema::table('aids', function (Blueprint $table): void {
            $table->timestamp('approval_escalated_at')->nullable()->after('decided_at');
        });
    }

    public function down(): void
    {
        Schema::table('approval_flow_stages', function (Blueprint $table): void {
            $table->dropColumn('max_days');
        });

        Schema::table('aids', function (Blueprint $table): void {
            $table->dropColumn('approval_escalated_at');
        });
    }
};
