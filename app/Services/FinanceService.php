<?php

namespace App\Services;

use App\Models\StudentFeeAccount;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\AcademicYear;
use App\Models\StudentFeeAdjustment;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Exception;

class FinanceService
{
    /**
     * Process fee collection, update ledger, and issue sequential receipts.
     * Guaranteed safe against concurrent modifications using row locks.
     */
    public function collectPayment(array $data, int $clerkId): Payment
    {
        return DB::transaction(function () use ($data, $clerkId) {
            $accountId = $data['student_fee_account_id'];
            $amount = (float) $data['amount'];

            // 1. Lock Student Fee Account for updates
            $feeAccount = StudentFeeAccount::where('id', $accountId)
                ->lockForUpdate()
                ->firstOrFail();

            $remaining = $feeAccount->remaining_balance;

            if ($amount <= 0) {
                throw new InvalidArgumentException("Payment amount must be greater than zero.");
            }

            if ($amount > $remaining) {
                throw new InvalidArgumentException("Excessive payment attempted. Outstanding balance is ₹" . number_format($remaining, 2));
            }

            // 2. Create the Payment Record
            $payment = Payment::create([
                'student_fee_account_id' => $feeAccount->id,
                'amount' => $amount,
                'payment_mode' => $data['payment_mode'],
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'payment_date' => now(),
                'collected_by' => $clerkId,
                'status' => 'SUCCESS',
            ]);

            // 3. Increment Paid Amount and Recalculate State
            $feeAccount->total_paid = (float) $feeAccount->total_paid + $amount;
            $feeAccount->recalculateTotals();
            $feeAccount->save();

            // 4. Thread-safe Receipt Generation
            $academicYear = AcademicYear::query()
                ->where('academic_year_id', $feeAccount->academic_year_id)
                ->firstOrFail();
            $receiptNumber = $this->generateReceiptNumber($academicYear);

            $receipt = Receipt::create([
                'payment_id' => $payment->id,
                'receipt_number' => $receiptNumber,
                'status' => 'ACTIVE',
            ]);

            // 5. Build Audit Log
            $this->logAction(
                $clerkId,
                'PAYMENT_COLLECTION',
                "Collected payment of ₹{$amount} via {$payment->payment_mode} for Student Fee Account ID: {$feeAccount->id}. Receipt: {$receiptNumber}"
            );

            return $payment->load('receipt', 'feeAccount.student');
        });
    }

    /**
     * Safely cancel a payment. Restores the student's fee dues and flags records.
     * Payments are never deleted.
     */
    public function cancelPayment(int $paymentId, string $reason, int $userId): Payment
    {
        return DB::transaction(function () use ($paymentId, $reason, $userId) {
            $payment = Payment::where('id', $paymentId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status === 'CANCELLED') {
                throw new InvalidArgumentException("This payment is already cancelled.");
            }

            // 1. Rollback fee account paid value
            $feeAccount = StudentFeeAccount::where('id', $payment->student_fee_account_id)
                ->lockForUpdate()
                ->firstOrFail();

            $feeAccount->total_paid = max(0.00, (float) $feeAccount->total_paid - (float) $payment->amount);
            $feeAccount->recalculateTotals();
            $feeAccount->save();

            // 2. Terminate Receipt
            if ($payment->receipt) {
                $payment->receipt->update([
                    'status' => 'CANCELLED',
                    'cancelled_at' => now(),
                    'cancelled_by' => $userId,
                ]);
            }

            // 3. Mark Payment Cancelled
            $payment->update([
                'status' => 'CANCELLED',
                'cancelled_at' => now(),
                'cancelled_by' => $userId,
                'cancellation_reason' => $reason,
            ]);

            // 4. Log Action
            $this->logAction(
                $userId,
                'PAYMENT_CANCELLATION',
                "Cancelled payment ID: {$payment->id} (Amount: ₹{$payment->amount}). Reason: {$reason}"
            );

            return $payment;
        });
    }

    /**
     * Request adjustments/concessions (requires approval)
     */
    public function requestAdjustment(array $data, int $requesterId): StudentFeeAdjustment
    {
        return StudentFeeAdjustment::create([
            'student_fee_account_id' => $data['student_fee_account_id'],
            'adjustment_type' => $data['adjustment_type'],
            'sub_type' => $data['sub_type'],
            'amount' => $data['amount'],
            'reason' => $data['reason'],
            'requested_by' => $requesterId,
            'status' => 'PENDING',
        ]);
    }

    /**
     * Resolve pending concessions (Approve/Reject)
     */
    public function decideAdjustment(int $adjustmentId, string $status, ?string $remarks, int $deciderId): StudentFeeAdjustment
    {
        if (!in_array($status, ['APPROVED', 'REJECTED'])) {
            throw new InvalidArgumentException("Invalid decision status.");
        }

        return DB::transaction(function () use ($adjustmentId, $status, $remarks, $deciderId) {
            $adjustment = StudentFeeAdjustment::where('id', $adjustmentId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($adjustment->status !== 'PENDING') {
                throw new InvalidArgumentException("This adjustment request has already been processed.");
            }

            $adjustment->update([
                'status' => $status,
                'approved_by' => $deciderId,
                'decided_at' => now(),
                'decision_remarks' => $remarks,
            ]);

            if ($status === 'APPROVED') {
                $feeAccount = StudentFeeAccount::where('id', $adjustment->student_fee_account_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $feeAccount->concession_amount = (float) $feeAccount->concession_amount + (float) $adjustment->amount;
                $feeAccount->recalculateTotals();
                $feeAccount->save();
            }

            $this->logAction(
                $deciderId,
                'FEE_ADJUSTMENT_DECISION',
                "Adjustment ID: {$adjustment->id} resolved to {$status}. Remarks: {$remarks}"
            );

            return $adjustment;
        });
    }

    /**
     * Handles specific scenario where books fee rules change dynamically
     */
    public function updateBooksFeeApplied(int $feeAccountId, float $amount, int $userId): StudentFeeAccount
    {
        return DB::transaction(function () use ($feeAccountId, $amount, $userId) {
            $feeAccount = StudentFeeAccount::where('id', $feeAccountId)
                ->lockForUpdate()
                ->firstOrFail();

            $oldFee = $feeAccount->books_fee_applied;
            $feeAccount->books_fee_applied = $amount;
            $feeAccount->recalculateTotals();
            $feeAccount->save();

            $this->logAction(
                $userId,
                'BOOKS_FEE_UPDATE',
                "Updated books fee on Account ID {$feeAccountId} from ₹{$oldFee} to ₹{$amount}"
            );

            return $feeAccount;
        });
    }

    /**
     * Generate unique sequence-locked receipts: REC-[START_YEAR]-[6-DIGIT-SEQUENCE]
     */
    protected function generateReceiptNumber(AcademicYear $academicYear): string
    {
        $yearPart = explode('-', (string) $academicYear->year_name)[0] ?? date('Y');

        // Lock existing receipt records for matching year to construct safe sequential counter
        $lastReceipt = Receipt::where('receipt_number', 'LIKE', "REC-{$yearPart}-%")
            ->lockForUpdate()
            ->orderBy('id', 'desc')
            ->first();

        $nextSequence = 1;
        if ($lastReceipt) {
            $parts = explode('-', $lastReceipt->receipt_number);
            if (isset($parts[2])) {
                $nextSequence = ((int) $parts[2]) + 1;
            }
        }

        return sprintf("REC-%s-%06d", $yearPart, $nextSequence);
    }

    /**
     * Logs internal actions for clear accountability and security
     */
    protected function logAction(int $userId, string $action, string $description): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'table_name' => 'finance',
            'record_id' => null,
            'new_value' => $description,
            'ip_address' => request()->ip() ?? '127.0.0.1',
        ]);
    }
}