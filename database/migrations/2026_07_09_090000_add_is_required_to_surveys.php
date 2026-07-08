<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A survey-level "required" flag: when true the post-confirmation survey
 * can no longer be skipped by the beneficiary (all its questions must be
 * answered). Defaults to false to preserve the existing optional behaviour.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surveys', function (Blueprint $table): void {
            $table->boolean('is_required')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table): void {
            $table->dropColumn('is_required');
        });
    }
};
