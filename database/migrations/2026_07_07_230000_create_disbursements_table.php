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
        Schema::create('disbursements', function (Blueprint $table) {
            $table->id();

            // One disbursement per aid: enforced both here and by the
            // Approved -> InDisbursement AidStatus transition guard in
            // StartDisbursement.
            $table->foreignId('aid_id')->unique()->constrained('aids')->cascadeOnDelete();

            $table->string('method'); // App\Enums\DisbursementMethod
            $table->string('status')->default('pending'); // App\Enums\DisbursementStatus

            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('started_at')->nullable();

            $table->foreignId('delivered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('delivered_at')->nullable();

            $table->string('transfer_reference')->nullable();
            $table->string('receipt_number')->nullable();
            $table->string('courier_name')->nullable();

            // Bank-account snapshot at disbursement time: the masked form is
            // safe to display anywhere, the full holder name is encrypted at
            // rest (see Disbursement::casts()) and never exposed to the UI.
            $table->string('bank_account_masked')->nullable();
            $table->text('bank_account_holder_snapshot')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('confirmed_at')->nullable();

            $table->timestamps();

            $table->index('method');
            $table->index('status');
            $table->index('delivered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disbursements');
    }
};
