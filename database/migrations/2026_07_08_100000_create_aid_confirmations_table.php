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
        Schema::create('aid_confirmations', function (Blueprint $table) {
            $table->id();

            // One active confirmation record per aid: a resend/reminder
            // rotates the token on this same row (updateOrCreate keyed on
            // aid_id) rather than accumulating history rows.
            $table->foreignId('aid_id')->unique()->constrained('aids')->cascadeOnDelete();

            // sha256 hex digest (64 chars) of the raw token embedded in the
            // signed public link. The raw token itself is never persisted.
            $table->string('token_hash', 64);
            $table->index('token_hash');

            $table->dateTime('expires_at');
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('opened_at')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('reminder_sent_at')->nullable();

            $table->string('confirmed_ip', 45)->nullable();
            $table->text('confirmed_user_agent')->nullable();

            // 'sms', 'whatsapp' or 'sms+whatsapp' — whichever channel(s)
            // the link was actually sent through, so the reminder can
            // reuse the same channel(s).
            $table->string('channel')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aid_confirmations');
    }
};
