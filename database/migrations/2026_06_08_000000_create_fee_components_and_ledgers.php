<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create fee_components table
        Schema::create('fee_components', function (Blueprint $table) {
            $table->bigIncrements('component_id');
            $table->string('component_code', 30)->unique();
            $table->string('component_name', 100);
            $table->string('category', 30);
            $table->boolean('is_optional')->default(false);
            $table->string('status', 20)->default('ACTIVE');
            $table->timestamp('created_at')->nullable();
        });

        // Seed default components
        $now = now();
        $components = [
            ['component_code' => 'ADMISSION', 'component_name' => 'Admission Fee', 'category' => 'ADMISSION', 'is_optional' => false],
            ['component_code' => 'TERM1', 'component_name' => 'Term-1 Tuition Fee', 'category' => 'TUITION', 'is_optional' => false],
            ['component_code' => 'TERM2', 'component_name' => 'Term-2 Tuition Fee', 'category' => 'TUITION', 'is_optional' => false],
            ['component_code' => 'TERM3', 'component_name' => 'Term-3 Tuition Fee', 'category' => 'TUITION', 'is_optional' => false],
            ['component_code' => 'TEXTBOOK', 'component_name' => 'Text Books', 'category' => 'BOOKS', 'is_optional' => true],
            ['component_code' => 'NOTEBOOK', 'component_name' => 'Note Books', 'category' => 'BOOKS', 'is_optional' => true],
            ['component_code' => 'EXAM', 'component_name' => 'Exam Fee', 'category' => 'BOOKS', 'is_optional' => true],
            ['component_code' => 'DIARY', 'component_name' => 'School Diary', 'category' => 'BOOKS', 'is_optional' => true],
            ['component_code' => 'FILE', 'component_name' => 'Student File', 'category' => 'BOOKS', 'is_optional' => true],
            ['component_code' => 'BELT', 'component_name' => 'School Belt', 'category' => 'STORE', 'is_optional' => true],
            ['component_code' => 'TIE', 'component_name' => 'School Tie', 'category' => 'STORE', 'is_optional' => true],
            ['component_code' => 'TSHIRT', 'component_name' => 'School T-Shirt', 'category' => 'STORE', 'is_optional' => true],
            ['component_code' => 'PREV_TUITION_DUE', 'component_name' => 'Previous Tuition Dues', 'category' => 'CARRY_FORWARD', 'is_optional' => true],
            ['component_code' => 'PREV_BOOKS_DUE', 'component_name' => 'Previous Books Dues', 'category' => 'CARRY_FORWARD', 'is_optional' => true],
            ['component_code' => 'PREV_ADMISSION_DUE', 'component_name' => 'Previous Admission Dues', 'category' => 'CARRY_FORWARD', 'is_optional' => true],
        ];

        foreach ($components as $comp) {
            DB::table('fee_components')->insert(array_merge($comp, ['created_at' => $now]));
        }

        // 2. Create class_fee_components table
        Schema::create('class_fee_components', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('academic_year_id'); // signed to match DB
            $table->bigInteger('class_id'); // signed to match DB
            $table->unsignedBigInteger('component_id'); // unsigned referencing new table
            $table->decimal('amount', 10, 2);
            $table->timestamp('created_at')->nullable();

            $table->unique(['academic_year_id', 'class_id', 'component_id'], 'class_fee_comp_unique');
            $table->foreign('academic_year_id')->references('academic_year_id')->on('academic_years');
            $table->foreign('class_id')->references('class_id')->on('classes');
            $table->foreign('component_id')->references('component_id')->on('fee_components');
        });

        // 3. Create student_fee_component_accounts table
        Schema::create('student_fee_component_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('student_id'); // signed
            $table->bigInteger('enrollment_id'); // signed
            $table->unsignedBigInteger('component_id'); // unsigned
            $table->decimal('amount', 10, 2);
            $table->decimal('concession_amount', 10, 2)->default(0.00);
            $table->decimal('waiver_amount', 10, 2)->default(0.00);
            $table->decimal('paid_amount', 10, 2)->default(0.00);
            $table->decimal('balance_amount', 10, 2);
            $table->string('status', 20)->default('PENDING');
            $table->timestamp('created_at')->nullable();

            $table->unique(['enrollment_id', 'component_id'], 'stud_fee_comp_unique');
            $table->foreign('student_id')->references('student_id')->on('students');
            $table->foreign('enrollment_id')->references('enrollment_id')->on('student_enrollments');
            $table->foreign('component_id')->references('component_id')->on('fee_components');
        });

        // 4. Create student_fee_component_selections table
        Schema::create('student_fee_component_selections', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('student_id'); // signed
            $table->bigInteger('enrollment_id'); // signed
            $table->unsignedBigInteger('component_id'); // unsigned
            $table->decimal('amount', 10, 2);
            $table->bigInteger('selected_by'); // signed
            $table->timestamp('selected_at');

            $table->unique(['enrollment_id', 'component_id'], 'stud_fee_sel_unique');
            $table->foreign('student_id')->references('student_id')->on('students');
            $table->foreign('enrollment_id')->references('enrollment_id')->on('student_enrollments');
            $table->foreign('component_id')->references('component_id')->on('fee_components');
            $table->foreign('selected_by')->references('user_id')->on('users');
        });

        // 5. Create fee_waivers table
        Schema::create('fee_waivers', function (Blueprint $table) {
            $table->bigIncrements('waiver_id');
            $table->bigInteger('student_id'); // signed
            $table->bigInteger('enrollment_id'); // signed
            $table->unsignedBigInteger('component_id'); // unsigned
            $table->decimal('waiver_amount', 10, 2);
            $table->text('reason')->nullable();
            $table->bigInteger('approved_by'); // signed
            $table->timestamp('approved_at');

            $table->foreign('student_id')->references('student_id')->on('students');
            $table->foreign('enrollment_id')->references('enrollment_id')->on('student_enrollments');
            $table->foreign('component_id')->references('component_id')->on('fee_components');
            $table->foreign('approved_by')->references('user_id')->on('users');
        });

        // 6. Create payment_component_allocations table
        Schema::create('payment_component_allocations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('payment_id'); // signed
            $table->unsignedBigInteger('component_account_id'); // unsigned
            $table->decimal('amount_paid', 10, 2);
            $table->timestamp('created_at')->nullable();

            $table->foreign('payment_id')->references('payment_id')->on('payments')->onDelete('cascade');
            $table->foreign('component_account_id', 'fk_pca_component_account')->references('id')->on('student_fee_component_accounts');
        });

        // 7. Create receipt_component_details table
        Schema::create('receipt_component_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('receipt_id'); // signed
            $table->unsignedBigInteger('component_id'); // unsigned
            $table->string('component_name', 100);
            $table->decimal('previous_balance', 10, 2);
            $table->decimal('paid_amount', 10, 2);
            $table->decimal('remaining_balance', 10, 2);
            $table->timestamp('created_at')->nullable();

            $table->foreign('receipt_id')->references('receipt_id')->on('receipts')->onDelete('cascade');
            $table->foreign('component_id')->references('component_id')->on('fee_components');
        });

        // 8. Add admission_type column to students table
        if (!Schema::hasColumn('students', 'admission_type')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('admission_type', 30)->nullable()->after('status');
            });
        }

        // 9. Add component_id column to student_fee_adjustments table
        if (!Schema::hasColumn('student_fee_adjustments', 'component_id')) {
            Schema::table('student_fee_adjustments', function (Blueprint $table) {
                $table->unsignedBigInteger('component_id')->nullable()->after('account_id');
                $table->foreign('component_id')->references('component_id')->on('fee_components');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop new column from student_fee_adjustments
        if (Schema::hasColumn('student_fee_adjustments', 'component_id')) {
            Schema::table('student_fee_adjustments', function (Blueprint $table) {
                $table->dropForeign(['component_id']);
                $table->dropColumn('component_id');
            });
        }

        // Drop new column from students
        if (Schema::hasColumn('students', 'admission_type')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('admission_type');
            });
        }

        // Drop new tables
        Schema::dropIfExists('receipt_component_details');
        Schema::dropIfExists('payment_component_allocations');
        Schema::dropIfExists('fee_waivers');
        Schema::dropIfExists('student_fee_component_selections');
        Schema::dropIfExists('student_fee_component_accounts');
        Schema::dropIfExists('class_fee_components');
        Schema::dropIfExists('fee_components');
    }
};
