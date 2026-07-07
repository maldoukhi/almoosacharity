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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();

            $table->string('event'); // App\Enums\NotificationEvent value
            $table->string('channel'); // App\Enums\MessageChannel value
            $table->text('body');
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['event', 'channel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
