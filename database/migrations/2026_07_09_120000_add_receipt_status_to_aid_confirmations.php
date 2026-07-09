<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a beneficiary confirm *how much* of an aid they received, not just
 * that they received it: an overall receipt status (received / partial /
 * not_received), an optional free-text note, and — for in-kind aids — the
 * specific aid_items they acknowledge receiving. Null status = the legacy
 * plain "confirmed" behaviour (treated as fully received).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aid_confirmations', function (Blueprint $table): void {
            $table->string('receipt_status')->nullable()->after('confirmed_at');
            $table->text('receipt_note')->nullable()->after('receipt_status');
            $table->json('received_item_ids')->nullable()->after('receipt_note');
        });
    }

    public function down(): void
    {
        Schema::table('aid_confirmations', function (Blueprint $table): void {
            $table->dropColumn(['receipt_status', 'receipt_note', 'received_item_ids']);
        });
    }
};
