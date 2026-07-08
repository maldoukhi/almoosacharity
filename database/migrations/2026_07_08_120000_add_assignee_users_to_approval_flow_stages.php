<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A stage can now be assigned to specific user(s), a role, or both. The
 * role therefore becomes optional (a stage may target only named people),
 * and assignee_user_ids holds the ids of specifically-assigned users.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_flow_stages', function (Blueprint $table): void {
            $table->json('assignee_user_ids')->nullable()->after('role');
        });

        Schema::table('approval_flow_stages', function (Blueprint $table): void {
            $table->string('role')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('approval_flow_stages', function (Blueprint $table): void {
            $table->dropColumn('assignee_user_ids');
        });

        Schema::table('approval_flow_stages', function (Blueprint $table): void {
            $table->string('role')->nullable(false)->change();
        });
    }
};
