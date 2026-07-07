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
        Schema::table('users', function (Blueprint $table) {
            $table->string('status')->default('active')->after('password');
            $table->string('preferred_locale', 5)->default('ar')->after('status');
            $table->string('phone')->nullable()->after('preferred_locale');
            $table->string('job_title')->nullable()->after('phone');
            $table->timestamp('last_login_at')->nullable()->after('job_title');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'status',
                'preferred_locale',
                'phone',
                'job_title',
                'last_login_at',
            ]);
        });
    }
};
