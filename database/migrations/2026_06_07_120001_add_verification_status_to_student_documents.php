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
        Schema::table('student_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('student_documents', 'verification_status')) {
                $table->string('verification_status', 20)->default('UPLOADED')->after('file_path');
            }

            if (!Schema::hasColumn('student_documents', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verification_status');
            }

            if (!Schema::hasColumn('student_documents', 'verified_by')) {
                $table->unsignedBigInteger('verified_by')->nullable()->after('verified_at');
                $table->foreign('verified_by')->references('user_id')->on('users');
            }

            if (!Schema::hasColumn('student_documents', 'remarks')) {
                $table->text('remarks')->nullable()->after('verified_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_documents', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn([
                'verification_status',
                'verified_at',
                'verified_by',
                'remarks',
            ]);
        });
    }
};
