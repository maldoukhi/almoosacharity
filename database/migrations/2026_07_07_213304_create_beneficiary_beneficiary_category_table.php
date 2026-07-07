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
        Schema::create('beneficiary_beneficiary_category', function (Blueprint $table) {
            $table->foreignId('beneficiary_id')->constrained('beneficiaries')->cascadeOnDelete();
            $table->foreignId('beneficiary_category_id')->constrained('beneficiary_categories')->cascadeOnDelete();

            $table->unique(['beneficiary_id', 'beneficiary_category_id'], 'beneficiary_category_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beneficiary_beneficiary_category');
    }
};
