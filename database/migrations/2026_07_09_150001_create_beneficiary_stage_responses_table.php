<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The beneficiary-facing "respond to this stage" link state for an aid
 * that has advanced into a beneficiary_response approval stage. Mirrors
 * aid_confirmations: one row per (aid, stage), whose token is rotated in
 * place on every (re)send rather than kept as history rows. The raw token
 * is never persisted — only its sha256 digest.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiary_stage_responses', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('aid_id')->constrained('aids')->cascadeOnDelete();
            $table->foreignId('approval_flow_stage_id')->nullable()->constrained('approval_flow_stages')->nullOnDelete();

            // One active response record per (aid, stage): a resend rotates
            // the token on this same row (updateOrCreate keyed on both).
            $table->unique(['aid_id', 'approval_flow_stage_id']);

            // Snapshot of the stage name at the time the link was issued.
            $table->string('stage_name');

            // sha256 hex digest (64 chars) of the raw token embedded in the
            // public link. The raw token itself is never persisted.
            $table->string('token_hash', 64);
            $table->index('token_hash');

            $table->dateTime('expires_at');
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('opened_at')->nullable();
            $table->dateTime('responded_at')->nullable();

            // The beneficiary's optional free-text note submitted with the
            // response.
            $table->text('note')->nullable();

            $table->string('responded_ip', 45)->nullable();
            $table->text('responded_user_agent')->nullable();

            // 'sms', 'whatsapp' or 'sms+whatsapp' — whichever channel(s) the
            // link was actually sent through.
            $table->string('channel')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiary_stage_responses');
    }
};
