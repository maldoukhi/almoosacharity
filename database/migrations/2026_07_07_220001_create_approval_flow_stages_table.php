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
        Schema::create('approval_flow_stages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('approval_flow_id')->constrained('approval_flows')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('order');
            $table->string('role')->index(); // App\Enums\RoleName value
            $table->json('allowed_actions'); // array of App\Enums\ApprovalAction values

            $table->timestamps();

            $table->unique(['approval_flow_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_flow_stages');
    }
};
