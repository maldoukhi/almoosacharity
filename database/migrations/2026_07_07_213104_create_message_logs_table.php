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
        Schema::create('message_logs', function (Blueprint $table) {
            $table->id();
            $table->string('channel'); // App\Enums\MessageChannel
            $table->string('provider'); // e.g. taqnyat, okta, fake
            $table->string('recipient');
            $table->text('body');
            $table->string('template_name')->nullable();
            $table->string('status')->default('pending'); // App\Enums\MessageStatus
            $table->string('provider_message_id')->nullable();
            $table->text('error')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->nullableMorphs('messageable');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('channel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_logs');
    }
};
