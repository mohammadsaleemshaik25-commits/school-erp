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
        Schema::create('admissions', function (Blueprint $table) {
            $table->bigIncrements('admission_id');
            $table->bigInteger('student_id');
            $table->bigInteger('academic_year_id');
            $table->bigInteger('class_id');
            $table->bigInteger('section_id');
            $table->string('admission_status', 20)->default('APPROVED');
            $table->text('remarks')->nullable();
            $table->bigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->bigInteger('created_by');
            $table->timestamps();

            // Relationships
            $table->foreign('student_id')->references('student_id')->on('students');
            $table->foreign('academic_year_id')->references('academic_year_id')->on('academic_years');
            $table->foreign('class_id')->references('class_id')->on('classes');
            $table->foreign('section_id')->references('section_id')->on('sections');
            $table->foreign('created_by')->references('user_id')->on('users');
            $table->foreign('approved_by')->references('user_id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admissions');
    }
};
