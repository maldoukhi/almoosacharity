<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `message_templates` holds free-form, reusable broadcast texts staff
     * can save from the messaging screen ("save this as a template") and
     * reapply later. Deliberately separate from `notification_templates`
     * (one row per event x channel, driving automatic aid lifecycle
     * notifications): these are named, ad-hoc snippets with no fixed
     * event, optionally scoped to a single channel.
     */
    public function up(): void
    {
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('channel')->nullable(); // App\Enums\MessageChannel; null = usable for any channel
            $table->text('body');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_templates');
    }
};
