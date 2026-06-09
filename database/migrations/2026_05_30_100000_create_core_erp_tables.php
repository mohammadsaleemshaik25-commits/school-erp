<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academic_years')) {
            Schema::create('academic_years', function (Blueprint $table): void {
                $table->bigIncrements('academic_year_id');
                $table->string('year_name', 20)->unique();
                $table->date('start_date');
                $table->date('end_date');
                $table->boolean('is_active')->default(false);
                $table->timestamp('created_at')->nullable()->useCurrent();
            });
        }

        if (! Schema::hasTable('classes')) {
            Schema::create('classes', function (Blueprint $table): void {
                $table->bigIncrements('class_id');
                $table->string('class_name', 50)->unique();
                $table->unsignedInteger('display_order')->default(1);
                $table->timestamp('created_at')->nullable()->useCurrent();
            });
        }

        if (! Schema::hasTable('sections')) {
            Schema::create('sections', function (Blueprint $table): void {
                $table->bigIncrements('section_id');
                $table->unsignedBigInteger('class_id');
                $table->string('section_name', 20);
                $table->unique(['class_id', 'section_name']);
                $table->timestamp('created_at')->nullable()->useCurrent();
            });
        }

        if (! Schema::hasTable('students')) {
            Schema::create('students', function (Blueprint $table): void {
                $table->bigIncrements('student_id');
                $table->string('admission_no', 30)->unique();
                $table->string('pen_no', 30)->unique();
                $table->string('aadhaar_no', 20)->unique();
                $table->string('student_name', 100);
                $table->date('dob');
                $table->string('gender', 10);
                $table->string('father_name', 100);
                $table->string('mother_name', 100)->nullable();
                $table->string('guardian_name', 100)->nullable();
                $table->string('phone_primary', 15)->nullable();
                $table->string('phone_secondary', 15)->nullable();
                $table->string('email', 100)->nullable();
                $table->text('address')->nullable();
                $table->date('admission_date');
                $table->string('status', 20)->default('ACTIVE');
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent();
            });
        }

        if (! Schema::hasTable('student_enrollments')) {
            Schema::create('student_enrollments', function (Blueprint $table): void {
                $table->bigIncrements('enrollment_id');
                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('academic_year_id');
                $table->unsignedBigInteger('class_id');
                $table->unsignedBigInteger('section_id');
                $table->string('promotion_status', 20)->nullable();
                $table->string('status', 20)->default('ACTIVE');
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (! Schema::hasTable('student_documents')) {
            Schema::create('student_documents', function (Blueprint $table): void {
                $table->bigIncrements('document_id');
                $table->unsignedBigInteger('student_id');
                $table->string('document_type', 50);
                $table->string('file_name', 255);
                $table->string('file_path', 255)->nullable();
                $table->timestamp('uploaded_at')->nullable();
            });
        }

        if (! Schema::hasTable('fee_structures')) {
            Schema::create('fee_structures', function (Blueprint $table): void {
                $table->bigIncrements('fee_structure_id');
                $table->unsignedBigInteger('academic_year_id');
                $table->unsignedBigInteger('class_id');
                $table->decimal('tuition_fee', 10, 2);
                $table->decimal('books_fee', 10, 2);
                $table->timestamp('created_at')->nullable()->useCurrent();
            });
        }

        if (! Schema::hasTable('student_fee_accounts')) {
            Schema::create('student_fee_accounts', function (Blueprint $table): void {
                $table->bigIncrements('account_id');
                $table->unsignedBigInteger('enrollment_id');
                $table->unsignedBigInteger('fee_structure_id')->nullable();
                $table->decimal('discount_amount', 10, 2)->default(0);
                $table->decimal('books_fee_applied', 10, 2)->default(0);
                $table->decimal('books_fee', 10, 2)->default(0);
                $table->decimal('net_fee', 10, 2)->default(0);
                $table->decimal('previous_balance', 10, 2)->default(0);
                $table->decimal('waived_amount', 10, 2)->default(0);
                $table->decimal('total_due', 10, 2)->default(0);
                $table->string('status', 20)->default('UNPAID');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table): void {
                $table->bigIncrements('payment_id');
                $table->unsignedBigInteger('account_id');
                $table->decimal('amount', 10, 2);
                $table->decimal('books_fee_paid', 10, 2)->default(0);
                $table->decimal('tuition_fee_paid', 10, 2)->default(0);
                $table->string('payment_mode', 20);
                $table->string('transaction_reference', 100)->nullable();
                $table->timestamp('payment_date')->nullable();
                $table->unsignedBigInteger('collected_by')->nullable();
                $table->string('status', 20)->default('SUCCESS');
                $table->timestamp('cancelled_at')->nullable();
                $table->unsignedBigInteger('cancelled_by')->nullable();
                $table->string('cancellation_reason', 255)->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('receipts')) {
            Schema::create('receipts', function (Blueprint $table): void {
                $table->bigIncrements('receipt_id');
                $table->unsignedBigInteger('payment_id');
                $table->string('receipt_number', 50)->unique();
                $table->string('status', 20)->default('ACTIVE');
                $table->timestamp('cancelled_at')->nullable();
                $table->unsignedBigInteger('cancelled_by')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('student_fee_adjustments')) {
            Schema::create('student_fee_adjustments', function (Blueprint $table): void {
                $table->bigIncrements('student_fee_adjustment_id');
                $table->unsignedBigInteger('account_id');
                $table->string('adjustment_type', 40);
                $table->string('sub_type', 60)->nullable();
                $table->decimal('amount', 10, 2);
                $table->text('reason')->nullable();
                $table->unsignedBigInteger('requested_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->string('status', 20)->default('PENDING');
                $table->timestamp('decided_at')->nullable();
                $table->text('decision_remarks')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fee_adjustments');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('student_fee_accounts');
        Schema::dropIfExists('fee_structures');
        Schema::dropIfExists('student_documents');
        Schema::dropIfExists('student_enrollments');
        Schema::dropIfExists('students');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('academic_years');
    }
};
