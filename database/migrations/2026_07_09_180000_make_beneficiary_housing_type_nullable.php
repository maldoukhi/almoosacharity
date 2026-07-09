<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * housing_type was NOT NULL + enum-cast, so a blank/invalid value (e.g. an
 * imported beneficiary whose housing column wasn't mapped, stored as '')
 * threw a ValueError the moment the profile read the attribute, 500-ing the
 * "housing & income" tab. Make it nullable and scrub any value that isn't a
 * real HousingType down to null so the enum cast can never blow up.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table): void {
            $table->string('housing_type')->nullable()->change();
        });

        DB::table('beneficiaries')
            ->whereNotIn('housing_type', ['owned', 'rented', 'shared', 'charity', 'other'])
            ->update(['housing_type' => null]);
    }

    public function down(): void
    {
        DB::table('beneficiaries')->whereNull('housing_type')->update(['housing_type' => 'other']);

        Schema::table('beneficiaries', function (Blueprint $table): void {
            $table->string('housing_type')->nullable(false)->change();
        });
    }
};
