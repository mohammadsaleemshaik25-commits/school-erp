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
        Schema::table('students', function (Blueprint $table) {
            // Add missing fields if they don't exist
            if (!Schema::hasColumn('students', 'permanent_address')) {
                $table->text('permanent_address')->nullable()->after('address');
            }

            if (!Schema::hasColumn('students', 'village')) {
                $table->string('village', 100)->nullable()->after('permanent_address');
            }

            if (!Schema::hasColumn('students', 'district')) {
                $table->string('district', 100)->nullable()->after('village');
            }

            if (!Schema::hasColumn('students', 'state')) {
                $table->string('state', 100)->nullable()->after('district');
            }

            if (!Schema::hasColumn('students', 'pin_code')) {
                $table->string('pin_code', 10)->nullable()->after('state');
            }

            if (!Schema::hasColumn('students', 'religion')) {
                $table->string('religion', 50)->nullable()->after('pin_code');
            }

            if (!Schema::hasColumn('students', 'category')) {
                $table->string('category', 50)->nullable()->after('religion');
            }

            if (!Schema::hasColumn('students', 'blood_group')) {
                $table->string('blood_group', 10)->nullable()->after('category');
            }

            if (!Schema::hasColumn('students', 'occupation')) {
                $table->string('occupation', 100)->nullable()->after('blood_group');
            }

            if (!Schema::hasColumn('students', 'annual_income')) {
                $table->decimal('annual_income', 12, 2)->nullable()->after('occupation');
            }

            if (!Schema::hasColumn('students', 'previous_school')) {
                $table->string('previous_school', 200)->nullable()->after('annual_income');
            }

            if (!Schema::hasColumn('students', 'previous_class')) {
                $table->string('previous_class', 50)->nullable()->after('previous_school');
            }

            if (!Schema::hasColumn('students', 'nationality')) {
                $table->string('nationality', 50)->default('Indian')->after('gender');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'permanent_address',
                'village',
                'district',
                'state',
                'pin_code',
                'religion',
                'category',
                'blood_group',
                'occupation',
                'annual_income',
                'previous_school',
                'previous_class',
                'nationality',
            ]);
        });
    }
};
