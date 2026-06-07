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
        // 1. Align student_fee_accounts
        Schema::table('student_fee_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('student_fee_accounts', 'books_from_school')) {
                $table->boolean('books_from_school')->default(true)->after('books_status');
            }
            if (!Schema::hasColumn('student_fee_accounts', 'final_tuition_fee')) {
                $table->decimal('final_tuition_fee', 10, 2)->default(0)->after('discount_amount');
            }
            if (!Schema::hasColumn('student_fee_accounts', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('status');
            }
        });

        // 2. Align payments
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'fee_component_type')) {
                $table->string('fee_component_type', 20)->default('TUITION')->after('collected_by');
            }
            if (!Schema::hasColumn('payments', 'receipt_generated')) {
                $table->boolean('receipt_generated')->default(false)->after('status');
            }
            if (!Schema::hasColumn('payments', 'remarks')) {
                $table->text('remarks')->nullable()->after('payment_date');
            }
            // Drop non-schema columns
            if (Schema::hasColumn('payments', 'cancelled_at')) {
                $table->dropColumn(['cancelled_at', 'cancelled_by', 'cancellation_reason']);
            }
        });

        // 3. Align receipts
        Schema::table('receipts', function (Blueprint $table) {
            if (!Schema::hasColumn('receipts', 'receipt_date')) {
                $table->date('receipt_date')->nullable()->after('receipt_number');
            }
            if (!Schema::hasColumn('receipts', 'generated_datetime')) {
                $table->timestamp('generated_datetime')->nullable()->after('receipt_date');
            }
            if (!Schema::hasColumn('receipts', 'generated_by')) {
                $table->unsignedBigInteger('generated_by')->nullable()->after('generated_datetime');
            }
            if (!Schema::hasColumn('receipts', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('status');
            }
            if (!Schema::hasColumn('receipts', 'is_duplicate')) {
                $table->boolean('is_duplicate')->default(false)->after('cancellation_reason');
            }
            if (!Schema::hasColumn('receipts', 'printed_count')) {
                $table->unsignedInteger('printed_count')->default(0)->after('is_duplicate');
            }
            // Drop non-schema columns
            if (Schema::hasColumn('receipts', 'cancelled_at')) {
                $table->dropColumn(['cancelled_at', 'cancelled_by']);
            }
        });

        // 4. Align student_fee_adjustments
        Schema::table('student_fee_adjustments', function (Blueprint $table) {
            if (Schema::hasColumn('student_fee_adjustments', 'student_fee_adjustment_id')) {
                $table->renameColumn('student_fee_adjustment_id', 'adjustment_id');
            }
            if (!Schema::hasColumn('student_fee_adjustments', 'discount_percent')) {
                $table->decimal('discount_percent', 5, 2)->default(0)->after('adjustment_type');
            }
            if (!Schema::hasColumn('student_fee_adjustments', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0)->after('discount_percent');
            }
            // Drop old columns if they exist
            if (Schema::hasColumn('student_fee_adjustments', 'amount')) {
                $table->dropColumn('amount');
            }
            if (Schema::hasColumn('student_fee_adjustments', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('student_fee_adjustments', 'decided_at')) {
                $table->dropColumn('decided_at');
            }
            if (Schema::hasColumn('student_fee_adjustments', 'decision_remarks')) {
                $table->dropColumn('decision_remarks');
            }
            if (Schema::hasColumn('student_fee_adjustments', 'sub_type')) {
                $table->dropColumn('sub_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not implemented for this final alignment
    }
};
