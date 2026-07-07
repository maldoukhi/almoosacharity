<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('aids', function (Blueprint $table) {
            $table->id();

            $table->string('reference')->unique();
            $table->foreignId('beneficiary_id')->constrained('beneficiaries')->restrictOnDelete();
            $table->foreignId('aid_program_id')->constrained('aid_programs')->restrictOnDelete();
            $table->string('type'); // App\Enums\AidType
            $table->string('status')->default('draft'); // App\Enums\AidStatus
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('purpose')->nullable();
            $table->text('notes')->nullable();

            // Snapshot of the approval flow assigned at submission time, so
            // later edits to the flow configuration never retroactively
            // change an in-flight or historical aid's workflow.
            $table->foreignId('approval_flow_id')->nullable()->constrained('approval_flows')->nullOnDelete();
            $table->foreignId('current_stage_id')->nullable()->constrained('approval_flow_stages')->nullOnDelete();

            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decided_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'current_stage_id']);
            $table->index(['status', 'aid_program_id']);
            $table->index('beneficiary_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aids');
    }
};
