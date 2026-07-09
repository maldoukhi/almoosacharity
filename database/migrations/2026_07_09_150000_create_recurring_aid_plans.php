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
        Schema::create('recurring_aid_plans', function (Blueprint $table) {
            $table->id();

            // The originating aid this plan clones each cycle. Deleting the
            // source aid tears down its plan too.
            $table->foreignId('aid_id')->constrained('aids')->cascadeOnDelete();

            $table->string('frequency'); // App\Enums\RecurrenceFrequency
            // Only meaningful for the CustomMonths frequency.
            $table->unsignedSmallInteger('interval_months')->nullable();

            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            // The next date the generator should clone the source aid on.
            $table->date('next_run_on');

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['is_active', 'next_run_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_aid_plans');
    }
};
