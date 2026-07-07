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
        Schema::create('approval_decisions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('aid_id')->constrained('aids')->cascadeOnDelete();
            $table->foreignId('approval_flow_stage_id')->nullable()->constrained('approval_flow_stages')->nullOnDelete();
            $table->string('stage_name'); // snapshot of the stage name at decision time
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('action'); // App\Enums\ApprovalAction
            $table->text('note')->nullable();
            $table->timestamp('decided_at');

            $table->timestamps();

            $table->index(['aid_id', 'decided_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_decisions');
    }
};
