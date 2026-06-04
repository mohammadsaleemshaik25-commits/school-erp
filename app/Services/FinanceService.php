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
            $accountId = $data['account_id'];
            $amount = (float) $data['amount'];
            if (floor($amount) != $amount) {
                      throw new InvalidArgumentException(
                      "Only whole rupee amounts are allowed.");
}


            // 1. Lock Student Fee Account for updates
            $feeAccount = StudentFeeAccount::where('account_id', $accountId)
                ->lockForUpdate()
                ->firstOrFail();

            $remaining = $feeAccount->remaining_balance;

            if ($amount <= 0) {
                throw new InvalidArgumentException("Payment amount must be greater than zero.");
            }

            if ($amount > $remaining) {
                throw new InvalidArgumentException("Excessive payment attempted. Outstanding balance is ₹" . number_format($remaining, 2));
            }

            // --- DUPLICATE PAYMENT PROTECTION ---
            $recentPayment = Payment::where('account_id', $accountId)
                ->where('amount', $amount)
                ->where('status', 'SUCCESS')
                ->where('created_at', '>=', now()->subSeconds(30))
                ->first();
            
            if ($recentPayment) {
                throw new Exception("A similar payment of ₹" . number_format($amount, 2) . " was processed just seconds ago. To prevent duplicates, please wait a moment or check the ledger.");
            }

            // --- PAYMENT PROTECTION VALIDATION ---
            if ($feeAccount->booksPurchasedOutside()) {
                // If student is OUTSIDE, ensure books_fee_applied is 0 and amount is only for tuition
                if ((float)$feeAccount->books_fee_applied > 0) {
                     // Auto-fix if caught during payment
                     $feeAccount->books_fee_applied = 0.00;
                     $feeAccount->recalculateTotals();
                     $feeAccount->save();
                }
            }

            // 2. Create the Payment Record
            // Calculate Books vs Tuition allocation
            $booksDue = 0;
            $booksPaid = 0.00;
            $tuitionPaid = 0.00;

            if ($feeAccount->books_status === 'SCHOOL') {
                $totalBooksAlreadyPaid = (float) $feeAccount->payments()
                    ->where('status', 'SUCCESS')
                    ->sum('books_fee_paid');
                
                $booksRemaining = max(0, (float) $feeAccount->books_fee_applied - $totalBooksAlreadyPaid);

                if ($amount <= $booksRemaining) {
                    $booksPaid = $amount;
                    $tuitionPaid = 0.00;
                } else {
                    $booksPaid = $booksRemaining;
                    $tuitionPaid = $amount - $booksRemaining;
                }
            } else {
                // If BOOKS_PAID or OUTSIDE or PENDING, all goes to tuition
                // STRICT PROTECTION: Ensure booksPaid is ALWAYS 0 for OUTSIDE
                $booksPaid = 0.00;
                $tuitionPaid = $amount;
            }

            $feeComponentType = 'TUITION';
            if ($booksPaid > 0 && $tuitionPaid > 0) {
                $feeComponentType = 'MIXED';
            } elseif ($booksPaid > 0) {
                $feeComponentType = 'BOOKS';
            }

            // Re-verify protection for OUTSIDE status
            if ($feeAccount->booksPurchasedOutside()) {
                $booksPaid = 0.00;
                $tuitionPaid = $amount;
                $feeComponentType = 'TUITION';
            }

            // Check if books are now fully paid
            if ($feeAccount->books_status === 'SCHOOL') {
                $totalBooksPaidAfter = (float) $feeAccount->payments()
                    ->where('status', 'SUCCESS')
                    ->sum('books_fee_paid') + $booksPaid;
                
                if ($totalBooksPaidAfter >= (float) $feeAccount->books_fee_applied) {
                    $feeAccount->books_status = 'BOOKS_PAID';
                }
            }

            $payment = Payment::create([
                'account_id' => $feeAccount->account_id,
                'fee_component_type' => $feeComponentType,
                'amount' => $amount,
                'books_fee_paid' => $booksPaid,
                'tuition_fee_paid' => $tuitionPaid,
                'payment_mode' => $data['payment_mode'],
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'payment_date' => now(),
                'collected_by' => $clerkId,
                'status' => 'SUCCESS',
            ]);

            // 3. Recalculate State
            $feeAccount->recalculateTotals();
            $feeAccount->save();

            // 4. Thread-safe Receipt Generation
            $enrollment = $feeAccount->enrollment;
            if (!$enrollment) {
                throw new Exception("Enrollment record missing for account.");
            }
            
            $academicYear = $enrollment->academicYear;
            $receiptNumber = $this->generateReceiptNumber($academicYear);

            $receipt = Receipt::create([
                'payment_id' => $payment->payment_id,
                'receipt_number' => $receiptNumber,
                'receipt_date' => now(),
                'generated_datetime' => now(),
                'generated_by' => $clerkId,
                'status' => 'ACTIVE',
            ]);

            // 6. Build Audit Log
            $this->logAction(
                $clerkId,
                'PAYMENT_COLLECTION',
                "Collected payment of ₹{$amount} via {$payment->payment_mode} for Student Fee Account ID: {$feeAccount->account_id}. Receipt: {$receiptNumber}"
            );

            return $payment->load('receipt', 'feeAccount.enrollment.student');
        });
    }

    /**
     * Safely cancel a payment. Restores the student's fee dues and flags records.
     * Payments are never deleted.
     */
    public function cancelPayment(int $paymentId, string $reason, int $userId): Payment
    {
        return DB::transaction(function () use ($paymentId, $reason, $userId) {
            $payment = Payment::where('payment_id', $paymentId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status === 'CANCELLED') {
                throw new InvalidArgumentException("This payment is already cancelled.");
            }

            // 1. Rollback fee account status
            $feeAccount = StudentFeeAccount::where('account_id', $payment->account_id)
                ->lockForUpdate()
                ->firstOrFail();

            // 2. Terminate Receipt
            if ($payment->receipt) {
                $payment->receipt->update([
                    'status' => 'CANCELLED',
                    'cancellation_reason' => $reason,
                ]);
            }

            // 3. Mark Payment Cancelled
            $payment->update([
                'status' => 'CANCELLED',
            ]);

            // Recalculate account status after cancellation
            $feeAccount->recalculateTotals();
            $feeAccount->save();

            // 4. Log Action
            $this->logAction(
                $userId,
                'PAYMENT_CANCELLATION',
                "Cancelled payment ID: {$payment->payment_id} (Amount: ₹{$payment->amount}). Reason: {$reason}"
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
            'account_id' => $data['account_id'],
            'adjustment_type' => $data['adjustment_type'],
            'discount_amount' => $data['discount_amount'],
            'discount_percent' => $data['discount_percent'] ?? 0,
            'reason' => $data['reason'],
            'requested_by' => $requesterId,
            'approval_status' => 'PENDING',
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
            $adjustment = StudentFeeAdjustment::where('adjustment_id', $adjustmentId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($adjustment->approval_status !== 'PENDING') {
                throw new InvalidArgumentException("This adjustment request has already been processed.");
            }

            $adjustment->update([
                'approval_status' => $status === 'APPROVED' ? 'APPROVED' : 'REJECTED',
                'approved_by' => $deciderId,
                'approved_at' => now(),
                'rejection_reason' => $status === 'REJECTED' ? $remarks : null,
            ]);

            if ($status === 'APPROVED') {
                $feeAccount = StudentFeeAccount::where('account_id', $adjustment->account_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $feeAccount->waived_amount = (float) $feeAccount->waived_amount + (float) $adjustment->discount_amount;
                $feeAccount->recalculateTotals();
                $feeAccount->save();
            }

            $this->logAction(
                $deciderId,
                'FEE_ADJUSTMENT_DECISION',
                "Adjustment ID: {$adjustment->adjustment_id} resolved to {$status}. Remarks: {$remarks}"
            );

            return $adjustment;
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
            ->orderBy('receipt_id', 'desc')
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
     * Update the books purchase decision for a student.
     * This is a one-time decision that freezes the books fee status.
     */
    public function updateBooksDecision(int $accountId, string $decision, int $clerkId): StudentFeeAccount
    {
        return DB::transaction(function () use ($accountId, $decision, $clerkId) {
            $feeAccount = StudentFeeAccount::where('account_id', $accountId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($feeAccount->books_status !== 'PENDING') {
                throw new Exception("Books decision has already been finalized for this account.");
            }

            // Check if any books payment already exists
            $booksPaid = $feeAccount->payments()
                ->where('status', 'SUCCESS')
                ->where('books_fee_paid', '>', 0)
                ->exists();
            
            if ($booksPaid) {
                throw new Exception("Cannot change books status because books payments have already been recorded.");
            }

            if ($decision === 'SCHOOL') {
                $feeAccount->books_status = 'SCHOOL';
                $feeAccount->books_from_school = true;
                // net_fee remains Tuition + Books
            } elseif ($decision === 'OUTSIDE') {
                $feeAccount->books_status = 'OUTSIDE';
                $feeAccount->books_from_school = false;
                $feeAccount->books_fee_applied = 0.00;
                $feeAccount->net_fee = $feeAccount->final_tuition_fee; // CRITICAL: Reset net_fee to tuition only
                $feeAccount->waived_amount += (float) $feeAccount->books_fee;
                $feeAccount->waived_by = $clerkId;
                $feeAccount->waived_date = now();
            } else {
                throw new InvalidArgumentException("Invalid books decision.");
            }

            $feeAccount->recalculateTotals();
            $feeAccount->save();

            $this->logAction(
                $clerkId,
                'BOOKS_DECISION',
                "Updated books decision to {$decision} for Student Fee Account ID: {$feeAccount->account_id}"
            );

            return $feeAccount;
        });
    }

    /**
     * Internal helper to log actions to audit_logs table.
     */
    protected function logAction(int $userId, string $action, string $details): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'table_name' => 'student_fee_accounts',
            'record_id' => null, // Can be refined
            'old_value' => null,
            'new_value' => $details,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Mark a receipt as duplicated and increment print count.
     */
    public function reprintReceipt(int $receiptId, int $userId): Receipt
    {
        return DB::transaction(function () use ($receiptId, $userId) {
            $receipt = Receipt::where('receipt_id', $receiptId)
                ->lockForUpdate()
                ->firstOrFail();

            $receipt->update([
                'is_duplicate' => true,
                'printed_count' => $receipt->printed_count + 1,
            ]);

            $this->logAction(
                $userId,
                'RECEIPT_REPRINT',
                "Reprinted receipt: {$receipt->receipt_number} (Total Prints: {$receipt->printed_count})"
            );

            return $receipt;
        });
    }
}