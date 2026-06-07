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
        Schema::table('student_fee_accounts', function (Blueprint $table) {
            $table->string('books_status', 20)->default('PENDING')->after('books_from_school');
        });

        // Data migration
        DB::table('student_fee_accounts')->where('books_from_school', 1)->update(['books_status' => 'SCHOOL']);
        DB::table('student_fee_accounts')->where('books_from_school', 0)->update(['books_status' => 'OUTSIDE']);

        Schema::table('student_fee_accounts', function (Blueprint $table) {
            $table->dropColumn('books_from_school');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_fee_accounts', function (Blueprint $table) {
            $table->boolean('books_from_school')->default(true)->after('books_status');
        });

        // Data migration back
        DB::table('student_fee_accounts')->where('books_status', 'SCHOOL')->update(['books_from_school' => 1]);
        DB::table('student_fee_accounts')->where('books_status', 'OUTSIDE')->update(['books_from_school' => 0]);
        DB::table('student_fee_accounts')->where('books_status', 'PENDING')->update(['books_from_school' => 0]);

        Schema::table('student_fee_accounts', function (Blueprint $table) {
            $table->dropColumn('books_status');
        });
    }
};
