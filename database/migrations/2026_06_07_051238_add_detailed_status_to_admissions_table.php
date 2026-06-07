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
        Schema::table('admissions', function (Blueprint $table) {
            if (!Schema::hasColumn('admissions', 'admission_status')) {
                $table->string('admission_status', 30)->default('DRAFT');
            } else {
                $table->string('admission_status', 30)->default('DRAFT')->change();
            }

            if (!Schema::hasColumn('admissions', 'transferred_from_admission_id')) {
                $table->unsignedBigInteger('transferred_from_admission_id')->nullable()->after('student_id');
                $table->foreign('transferred_from_admission_id')->references('admission_id')->on('admissions');
            }

            if (!Schema::hasColumn('admissions', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('remarks');
            }

            if (!Schema::hasColumn('admissions', 'verified_by')) {
                $table->unsignedBigInteger('verified_by')->nullable()->after('verified_at');
                $table->foreign('verified_by')->references('user_id')->on('users');
            }

            if (!Schema::hasColumn('admissions', 'admitted_at')) {
                $table->timestamp('admitted_at')->nullable()->after('approved_at');
            }

            if (!Schema::hasColumn('admissions', 'admitted_by')) {
                $table->unsignedBigInteger('admitted_by')->nullable()->after('admitted_at');
                $table->foreign('admitted_by')->references('user_id')->on('users');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropForeign(['transferred_from_admission_id']);
            $table->dropForeign(['verified_by']);
            $table->dropForeign(['admitted_by']);
            
            $table->dropColumn([
                'transferred_from_admission_id',
                'verified_at',
                'verified_by',
                'admitted_at',
                'admitted_by'
            ]);
            $table->string('admission_status', 20)->default('APPROVED')->change();
        });
    }
};
