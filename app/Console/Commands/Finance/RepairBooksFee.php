<?php

namespace App\Console\Commands\Finance;

use App\Models\StudentFeeAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairBooksFee extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:repair-books';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Repair student fee accounts where OUTSIDE students were incorrectly charged books fee.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $accounts = StudentFeeAccount::where('books_status', 'OUTSIDE')
            ->where(function ($query) {
                $query->where('books_fee_applied', '>', 0)
                      ->orWhereRaw('net_fee > final_tuition_fee');
            })
            ->get();

        if ($accounts->isEmpty()) {
            $this->info('No mismatched accounts found.');
            return 0;
        }

        $this->info("Found {$accounts->count()} mismatched accounts. Repairing...");
        $this->table(
            ['Account ID', 'Old Net', 'New Net', 'Old Due', 'New Due'],
            []
        );

        foreach ($accounts as $account) {
            $oldNet = (float) $account->net_fee;
            $oldDue = (float) $account->total_due;

            // Step 1: Fix net_fee to only reflect final_tuition_fee
            $account->net_fee = $account->final_tuition_fee;
            
            // Step 2: Ensure books_fee_applied is 0
            $account->books_fee_applied = 0.00;

            // Step 3: Recalculate total_due
            // Formula: total_due = final_tuition_fee + books_fee_applied + previous_balance - discount_amount - waived_amount
            $tuition = (float) $account->final_tuition_fee;
            $books = 0.00; // Force 0 for OUTSIDE
            $prevBalance = (float) $account->previous_balance;
            $discounts = (float) $account->discount_amount + (float) $account->waived_amount;
            
            $newDue = max(0, $tuition + $books + $prevBalance - $discounts);
            $account->total_due = $newDue;

            $this->line("ID: {$account->account_id} | Net: {$oldNet} -> {$account->net_fee} | Due: {$oldDue} -> {$newDue}");

            $account->save();
        }

        $this->info('Repair complete.');
        return 0;
    }
}
