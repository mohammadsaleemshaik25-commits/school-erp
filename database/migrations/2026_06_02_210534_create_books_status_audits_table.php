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
        if (!Schema::hasTable('books_status_audits')) {
            Schema::create('books_status_audits', function (Blueprint $table) {
                $table->bigIncrements('audit_id');
                $table->bigInteger('account_id')->unsigned();
                $table->bigInteger('user_id')->unsigned();
                $table->string('old_status', 20);
                $table->string('new_status', 20);
                $table->text('reason');
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('account_id')->references('account_id')->on('student_fee_accounts');
                $table->foreign('user_id')->references('user_id')->on('users');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books_status_audits');
    }
};
