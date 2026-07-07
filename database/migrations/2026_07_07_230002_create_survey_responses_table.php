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
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('survey_id')->constrained('surveys')->cascadeOnDelete();
            // The aid_confirmation_id column is added by a later migration in
            // the delivery-confirmation wave (phase 6b); this response can
            // stand alone (linked only to an aid/beneficiary) until then.
            $table->foreignId('aid_id')->nullable()->constrained('aids')->nullOnDelete();
            $table->foreignId('beneficiary_id')->nullable()->constrained('beneficiaries')->nullOnDelete();
            $table->dateTime('submitted_at');
            $table->string('ip', 45)->nullable();

            $table->timestamps();

            $table->index('survey_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
