<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('student_fee_accounts', 'books_from_school')) {
            Schema::table('student_fee_accounts', function (Blueprint $table) {
                $table->boolean('books_from_school')->default(true)->after('final_tuition_fee');
            });
        }

        if (! Schema::hasColumn('student_fee_accounts', 'books_fee_applied')) {
            Schema::table('student_fee_accounts', function (Blueprint $table) {
                $table->decimal('books_fee_applied', 10, 2)->default(0)->after('books_from_school');
            });
        }

        if (! Schema::hasColumn('student_fee_accounts', 'waived_by')) {
            Schema::table('student_fee_accounts', function (Blueprint $table) {
                $table->bigInteger('waived_by')->nullable()->after('waived_amount');
            });
        }

        DB::statement('ALTER TABLE student_fee_accounts MODIFY waived_by BIGINT NULL');

        if (! Schema::hasColumn('student_fee_accounts', 'waived_date')) {
            Schema::table('student_fee_accounts', function (Blueprint $table) {
                $table->date('waived_date')->nullable()->after('waived_by');
            });
        }

        Schema::table('student_fee_accounts', function (Blueprint $table) {
            $table->foreign('waived_by')->references('user_id')->on('users');
        });

        DB::table('student_fee_accounts')->update([
            'books_from_school' => true,
            'books_fee_applied' => DB::raw('books_fee'),
        ]);

        if (! Schema::hasColumn('payments', 'books_fee_paid')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->decimal('books_fee_paid', 10, 2)->default(0)->after('amount');
                $table->decimal('tuition_fee_paid', 10, 2)->default(0)->after('books_fee_paid');
            });
        }

        DB::statement('ALTER TABLE student_fee_adjustments MODIFY approved_by BIGINT NULL');

        if (! Schema::hasColumn('student_fee_adjustments', 'requested_by')) {
            Schema::table('student_fee_adjustments', function (Blueprint $table) {
                $table->bigInteger('requested_by')->nullable()->after('reason');
                $table->string('approval_status', 20)->default('APPROVED')->after('approved_by');
                $table->timestamp('approved_at')->nullable()->after('approval_status');
                $table->text('rejection_reason')->nullable()->after('approved_at');
                $table->foreign('requested_by')->references('user_id')->on('users');
            });
        }

        DB::table('student_fee_adjustments')
            ->whereNotNull('approved_by')
            ->update([
                'approval_status' => 'APPROVED',
                'approved_at' => DB::raw('created_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('student_fee_adjustments', function (Blueprint $table) {
            $table->dropForeign(['requested_by']);
            $table->dropColumn(['requested_by', 'approval_status', 'approved_at', 'rejection_reason']);
        });

        DB::statement('ALTER TABLE student_fee_adjustments MODIFY approved_by BIGINT NOT NULL');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['books_fee_paid', 'tuition_fee_paid']);
        });

        Schema::table('student_fee_accounts', function (Blueprint $table) {
            $table->dropForeign(['waived_by']);
            $table->dropColumn(['books_from_school', 'books_fee_applied', 'waived_by', 'waived_date']);
        });
    }
};
