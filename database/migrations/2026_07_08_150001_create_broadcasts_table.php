<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One row per bulk send from the beneficiaries broadcast screen. The
     * individual per-recipient sends are recorded in the existing
     * `message_logs` table via its polymorphic `messageable` relation
     * (messageable_type = App\Models\Broadcast), so this table only keeps
     * the summary (who sent it, to how many, with what text).
     */
    public function up(): void
    {
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->id();
            $table->string('channel'); // App\Enums\MessageChannel
            $table->text('body');
            $table->string('template_name')->nullable();
            $table->unsignedInteger('recipients_count')->default(0);
            // How many of recipients_count came from freely-typed numbers
            // (see App\Livewire\Messaging\Broadcast::$manualNumbers) rather
            // than a selected beneficiary.
            $table->unsignedInteger('manual_numbers_count')->default(0);
            $table->foreignId('sent_by')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('queued'); // App\Enums\BroadcastStatus
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('broadcasts');
    }
};
