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
        Schema::create('aid_batches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();

            // The program every aid in the batch was raised against. Nullable
            // at the schema level, though the batch screen always requires a
            // program because an Aid itself cannot exist without one.
            $table->foreignId('program_id')->nullable()->constrained('aid_programs')->nullOnDelete();

            $table->string('note')->nullable();

            $table->timestamps();
        });

        // Pivot linking a batch to every aid it raised. We deliberately keep
        // the batch->aid relationship out of the aids table so the aid model
        // stays untouched; a batch simply owns its aids through this table.
        Schema::create('aid_batch_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('aid_batch_id')->constrained('aid_batches')->cascadeOnDelete();
            $table->foreignId('aid_id')->constrained('aids')->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['aid_batch_id', 'aid_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aid_batch_items');
        Schema::dropIfExists('aid_batches');
    }
};
