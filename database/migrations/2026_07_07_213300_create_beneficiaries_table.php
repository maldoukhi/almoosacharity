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
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();

            $table->string('first_name');
            $table->string('second_name')->nullable();
            $table->string('third_name')->nullable();
            $table->string('last_name');

            $table->string('id_type')->default('national_id'); // App\Enums\IdType
            $table->string('national_id')->unique();
            $table->string('nationality')->default('SA');
            $table->date('birth_date')->nullable();
            $table->string('gender'); // App\Enums\Gender
            $table->string('mobile')->index();
            $table->string('marital_status'); // App\Enums\MaritalStatus
            $table->unsignedSmallInteger('family_members_count')->nullable();
            $table->string('occupation')->nullable();
            $table->string('employer')->nullable();
            $table->decimal('monthly_income', 12, 2)->nullable();
            $table->text('health_status')->nullable();
            $table->text('special_needs')->nullable();
            $table->string('housing_type'); // App\Enums\HousingType
            $table->decimal('rent_amount', 12, 2)->nullable();
            $table->string('national_address')->nullable();
            $table->string('city')->index();
            $table->string('district')->nullable();
            $table->string('bank_name')->nullable();
            $table->text('iban')->nullable(); // encrypted cast on the model
            $table->text('bank_account_holder')->nullable(); // encrypted cast on the model
            $table->string('status')->default('under_study')->index(); // App\Enums\BeneficiaryStatus
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'city']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};
