<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 10 (recurring aids, series link): every aid produced by a
     * recurring plan carries the plan's id here, grouping the generated
     * cycles into one "series". The originating (template) aid is *not*
     * linked this way — it owns the plan through recurring_aid_plans.aid_id
     * instead. Deleting the plan simply detaches its generated aids
     * (nullOnDelete), never removing them.
     */
    public function up(): void
    {
        Schema::table('aids', function (Blueprint $table) {
            $table->foreignId('recurring_aid_plan_id')
                ->nullable()
                ->after('created_by')
                ->constrained('recurring_aid_plans')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('aids', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recurring_aid_plan_id');
        });
    }
};
