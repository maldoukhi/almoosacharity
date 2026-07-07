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
        Schema::table('survey_responses', function (Blueprint $table) {
            // Links a response submitted right after the public delivery
            // confirmation flow back to the confirmation record it came
            // from, in addition to the existing aid_id/beneficiary_id.
            // Nullable: responses collected any other way (none yet, but
            // future-proofed) simply leave this null.
            $table->foreignId('aid_confirmation_id')
                ->nullable()
                ->after('beneficiary_id')
                ->constrained('aid_confirmations')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('aid_confirmation_id');
        });
    }
};
