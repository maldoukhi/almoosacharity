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
        Schema::create('beneficiary_flows', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();

            $table->timestamps();
        });

        Schema::create('beneficiary_flow_stages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('beneficiary_flow_id')->constrained('beneficiary_flows')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('order');
            $table->string('role')->nullable()->index(); // App\Enums\RoleName value
            $table->json('assignee_user_ids')->nullable(); // specific approver user ids
            $table->json('allowed_actions'); // array of App\Enums\ApprovalAction values

            $table->timestamps();

            $table->unique(['beneficiary_flow_id', 'order']);
        });

        Schema::create('beneficiary_decisions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('beneficiary_id')->constrained('beneficiaries')->cascadeOnDelete();
            $table->foreignId('beneficiary_flow_stage_id')->nullable()->constrained('beneficiary_flow_stages')->nullOnDelete();
            $table->string('stage_name'); // snapshot of the stage name at decision time
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('action'); // App\Enums\ApprovalAction
            $table->text('note')->nullable();
            $table->timestamp('decided_at');

            $table->timestamps();

            $table->index(['beneficiary_id', 'decided_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beneficiary_decisions');
        Schema::dropIfExists('beneficiary_flow_stages');
        Schema::dropIfExists('beneficiary_flows');
    }
};
