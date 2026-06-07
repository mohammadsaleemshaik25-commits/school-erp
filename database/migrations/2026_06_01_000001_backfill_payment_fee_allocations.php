<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('student_fee_accounts')
            ->orderBy('account_id')
            ->each(function ($account) {
                $remainingBooksFee = (float) $account->books_fee_applied;

                DB::table('payments')
                    ->where('account_id', $account->account_id)
                    ->where('status', 'ACTIVE')
                    ->orderBy('payment_id')
                    ->each(function ($payment) use (&$remainingBooksFee) {
                        $paymentAmount = (float) $payment->amount;
                        $booksFeePaid = min($paymentAmount, $remainingBooksFee);

                        DB::table('payments')
                            ->where('payment_id', $payment->payment_id)
                            ->update([
                                'books_fee_paid' => number_format($booksFeePaid, 2, '.', ''),
                                'tuition_fee_paid' => number_format($paymentAmount - $booksFeePaid, 2, '.', ''),
                            ]);

                        $remainingBooksFee -= $booksFeePaid;
                    });
            });
    }

    public function down(): void
    {
        DB::table('payments')->update([
            'books_fee_paid' => 0,
            'tuition_fee_paid' => 0,
        ]);
    }
};
