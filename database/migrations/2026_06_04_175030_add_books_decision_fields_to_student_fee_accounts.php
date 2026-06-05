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
        Schema::table('student_fee_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('student_fee_accounts', 'books_status')) {
                $table->string('books_status', 20)->default('PENDING')->after('final_tuition_fee');
            }
            
            if (Schema::hasColumn('student_fee_accounts', 'books_decision_by')) {
                // Change to match users.user_id type (signed bigint based on db:table output)
                $table->bigInteger('books_decision_by')->unsigned(false)->nullable()->change();
            } else {
                $table->bigInteger('books_decision_by')->unsigned(false)->nullable()->after('books_status');
            }

            if (!Schema::hasColumn('student_fee_accounts', 'books_decision_date')) {
                $table->timestamp('books_decision_date')->nullable()->after('books_decision_by');
            }
        });

        // Separate step for FK to avoid issues with change() in some DB drivers
        Schema::table('student_fee_accounts', function (Blueprint $table) {
            try {
                $table->foreign('books_decision_by')->references('user_id')->on('users');
            } catch (\Exception $e) {
                // Might already exist or still incompatible
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_fee_accounts', function (Blueprint $table) {
            try {
                $table->dropForeign(['books_decision_by']);
            } catch (\Exception $e) {}
            $table->dropColumn(['books_status', 'books_decision_by', 'books_decision_date']);
        });
    }
};
