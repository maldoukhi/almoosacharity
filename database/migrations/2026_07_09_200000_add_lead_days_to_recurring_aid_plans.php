<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 10 (recurring aids, prominence pass): a configurable lead time,
     * in days, that pulls each cycle's generation forward *before* its due
     * date. lead_days of 0 (the default) keeps the original behaviour —
     * generating exactly on next_run_on.
     */
    public function up(): void
    {
        Schema::table('recurring_aid_plans', function (Blueprint $table) {
            $table->unsignedSmallInteger('lead_days')->default(0)->after('next_run_on');
        });
    }

    public function down(): void
    {
        Schema::table('recurring_aid_plans', function (Blueprint $table) {
            $table->dropColumn('lead_days');
        });
    }
};
