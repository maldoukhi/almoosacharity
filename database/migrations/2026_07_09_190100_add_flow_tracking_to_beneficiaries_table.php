<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add the configurable-workflow tracking columns to beneficiaries: a
 * snapshot of the flow assigned at submission time (so later edits to a
 * flow never retroactively change an in-flight or historical beneficiary),
 * plus the current stage pointer and the submit/decide timestamps — exactly
 * as the aids table does for the aid approval flow.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table): void {
            $table->foreignId('beneficiary_flow_id')->nullable()->after('status')->constrained('beneficiary_flows')->nullOnDelete();
            $table->foreignId('current_stage_id')->nullable()->after('beneficiary_flow_id')->constrained('beneficiary_flow_stages')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('current_stage_id');
            $table->timestamp('decided_at')->nullable()->after('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('current_stage_id');
            $table->dropConstrainedForeignId('beneficiary_flow_id');
            $table->dropColumn(['submitted_at', 'decided_at']);
        });
    }
};
