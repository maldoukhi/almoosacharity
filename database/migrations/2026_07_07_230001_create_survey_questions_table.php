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
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('survey_id')->constrained('surveys')->cascadeOnDelete();
            $table->string('type'); // App\Enums\SurveyQuestionType
            $table->string('label');
            $table->text('help_text')->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('position')->default(1);
            $table->json('options')->nullable(); // choice question options: [{value, label}]
            $table->json('config')->nullable(); // e.g. {max_stars: 5} for rating questions

            $table->timestamps();

            $table->index(['survey_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_questions');
    }
};
