<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Richer approval-flow stages: a stage type (a classic approval, a
 * required document upload, or a wait-for-beneficiary-response), whether a
 * document is required and which document types, and the notification
 * channels used when the stage becomes actionable. Defaults preserve the
 * existing behaviour (plain approval, no required docs, in-app notify).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_flow_stages', function (Blueprint $table): void {
            $table->string('type')->default('approval')->after('order');
            $table->boolean('documents_required')->default(false)->after('type');
            $table->json('required_documents')->nullable()->after('documents_required');
            $table->json('notify_channels')->nullable()->after('required_documents');
        });
    }

    public function down(): void
    {
        Schema::table('approval_flow_stages', function (Blueprint $table): void {
            $table->dropColumn(['type', 'documents_required', 'required_documents', 'notify_channels']);
        });
    }
};
