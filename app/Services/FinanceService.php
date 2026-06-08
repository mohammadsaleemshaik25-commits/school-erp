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

            // Fetch previous years' outstanding fee accounts
            $studentId = $feeAccount->enrollment->student_id;
            $currentAY = $feeAccount->enrollment->academicYear;
            
            $prevAccounts = StudentFeeAccount::whereHas('enrollment', function($q) use ($studentId) {
                    $q->where('student_id', $studentId);
                })
                ->where('account_id', '!=', $feeAccount->account_id)
                ->whereIn('status', ['UNPAID', 'PARTIALLY_PAID'])
                ->get()
                ->filter(function($acc) use ($currentAY) {
                    $ay = $acc->enrollment->academicYear;
                    return $ay && $ay->start_date < $currentAY->start_date;
                })
                ->sortBy(function($acc) {
                    return $acc->enrollment->academicYear->start_date;
                });

            $prevAccountsDue = 0.00;
            foreach ($prevAccounts as $pa) {
                $prevAccountsDue += $pa->remaining_balance;
            }

            $currentAccountPrevPaid = (float) $feeAccount->payments()
                ->where('status', 'SUCCESS')
                ->sum('previous_fee_paid');
            $currentAccountPrevRemaining = max(0.00, (float)$feeAccount->previous_balance - $currentAccountPrevPaid);

            $prevRemaining = $prevAccountsDue + $currentAccountPrevRemaining;
            $totalOutstanding = $feeAccount->remaining_balance + $prevAccountsDue;

            if ($amount <= 0) {
                throw new InvalidArgumentException("Payment amount must be greater than zero.");
            }

            if ($amount > $totalOutstanding) {
                throw new InvalidArgumentException("Excessive payment attempted. Outstanding balance is ₹" . number_format($totalOutstanding, 2));
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
            $allocation = $data['allocation'] ?? 'TUITION';
            $overpaymentAllocation = $data['overpayment_allocation'] ?? 'TUITION';

            $booksRemaining = $feeAccount->remaining_books_balance;
            $tuitionRemaining = $feeAccount->remaining_tuition_balance;

            $booksPaid = 0.00;
            $tuitionPaid = 0.00;
            $previousPaid = 0.00;

            // Phase 1: Allocate according to clerk's primary choice
            if ($allocation === 'BOOKS') {
                if ($amount <= $booksRemaining) {
                    $booksPaid = $amount;
                } else {
                    $booksPaid = $booksRemaining;
                    $remainder = $amount - $booksRemaining;
                    // Allocate overpayment remainder
                    if ($overpaymentAllocation === 'PREVIOUS') {
                        if ($remainder <= $prevRemaining) {
                            $previousPaid = $remainder;
                        } else {
                            $previousPaid = $prevRemaining;
                            $tuitionPaid = $remainder - $prevRemaining;
                        }
                    } else { // default to TUITION
                        if ($remainder <= $tuitionRemaining) {
                            $tuitionPaid = $remainder;
                        } else {
                            $tuitionPaid = $tuitionRemaining;
                            $previousPaid = $remainder - $tuitionRemaining;
                        }
                    }
                }
            } elseif ($allocation === 'PREVIOUS') {
                if ($amount <= $prevRemaining) {
                    $previousPaid = $amount;
                } else {
                    $previousPaid = $prevRemaining;
                    $remainder = $amount - $prevRemaining;
                    // Allocate overpayment remainder
                    if ($overpaymentAllocation === 'BOOKS') {
                        if ($remainder <= $booksRemaining) {
                            $booksPaid = $remainder;
                        } else {
                            $booksPaid = $booksRemaining;
                            $tuitionPaid = $remainder - $booksRemaining;
                        }
                    } else { // default to TUITION
                        if ($remainder <= $tuitionRemaining) {
                            $tuitionPaid = $remainder;
                        } else {
                            $tuitionPaid = $tuitionRemaining;
                            $booksPaid = $remainder - $tuitionRemaining;
                        }
                    }
                }
            } else { // TUITION
                if ($amount <= $tuitionRemaining) {
                    $tuitionPaid = $amount;
                } else {
                    $tuitionPaid = $tuitionRemaining;
                    $remainder = $amount - $tuitionRemaining;
                    // Allocate overpayment remainder
                    if ($overpaymentAllocation === 'PREVIOUS') {
                        if ($remainder <= $prevRemaining) {
                            $previousPaid = $remainder;
                        } else {
                            $previousPaid = $prevRemaining;
                            $booksPaid = $remainder - $prevRemaining;
                        }
                    } else { // default to BOOKS
                        if ($remainder <= $booksRemaining) {
                            $booksPaid = $remainder;
                        } else {
                            $booksPaid = $booksRemaining;
                            $previousPaid = $remainder - $booksRemaining;
                        }
                    }
                }
            }

            // STRICT PROTECTION: Ensure booksPaid is ALWAYS 0 for OUTSIDE or if books purchased outside
            if ($feeAccount->booksPurchasedOutside()) {
                $tuitionPaid += $booksPaid;
                $booksPaid = 0.00;
            }

            // Set fee component type
            $feeComponentType = 'TUITION';
            $activeComponents = [];
            if ($booksPaid > 0) $activeComponents[] = 'BOOKS';
            if ($tuitionPaid > 0) $activeComponents[] = 'TUITION';
            if ($previousPaid > 0) $activeComponents[] = 'PREVIOUS';

            if (count($activeComponents) > 1) {
                $feeComponentType = 'MIXED';
            } elseif (count($activeComponents) === 1) {
                $feeComponentType = $activeComponents[0];
            }

            // Verify if books are now fully paid for current year
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
                'previous_fee_paid' => $previousPaid,
                'payment_mode' => $data['payment_mode'],
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'payment_date' => now(),
                'collected_by' => $clerkId,
                'status' => 'SUCCESS',
            ]);

            // If we have previous year payments, split them among outstanding previous accounts
            if ($previousPaid > 0) {
                $tempPrevPaid = $previousPaid;
                foreach ($prevAccounts as $pa) {
                    if ($tempPrevPaid <= 0) break;
                    $paRemaining = $pa->remaining_balance;
                    if ($paRemaining <= 0) continue;

                    $splitAmount = min($tempPrevPaid, $paRemaining);
                    $tempPrevPaid -= $splitAmount;

                    Payment::create([
                        'account_id' => $pa->account_id,
                        'fee_component_type' => 'TUITION',
                        'amount' => $splitAmount,
                        'books_fee_paid' => 0,
                        'tuition_fee_paid' => $splitAmount,
                        'previous_fee_paid' => 0,
                        'payment_mode' => $data['payment_mode'],
                        'transaction_reference' => $data['transaction_reference'] ?? null,
                        'payment_date' => now(),
                        'collected_by' => $clerkId,
                        'status' => 'SUCCESS',
                        'remarks' => "Split payment from Receipt of Main Payment ID: {$payment->payment_id}",
                        'receipt_generated' => false,
                    ]);

                    $pa->recalculateTotals();
                    $pa->save();
                }
            }

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

            // If this is a main payment with previous_fee_paid > 0, cancel any secondary split payments
            if ((float)$payment->previous_fee_paid > 0) {
                $secondaryPayments = Payment::where('remarks', "Split payment from Receipt of Main Payment ID: {$payment->payment_id}")
                    ->where('status', '!=', 'CANCELLED')
                    ->get();
                
                foreach ($secondaryPayments as $sp) {
                    $sp->update([
                        'status' => 'CANCELLED',
                    ]);
                    $spAccount = StudentFeeAccount::findOrFail($sp->account_id);
                    $spAccount->recalculateTotals();
                    $spAccount->save();
                }
            }

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
        $accountId = $data['account_id'];
        $discountAmount = (float) $data['discount_amount'];

        if ($discountAmount <= 0) {
            throw new InvalidArgumentException("Concession amount must be greater than zero.");
        }

        $feeAccount = StudentFeeAccount::where('account_id', $accountId)->firstOrFail();

        if ($discountAmount > (float) $feeAccount->final_tuition_fee) {
            throw new InvalidArgumentException("Concession cannot exceed the total tuition fee (₹" . number_format($feeAccount->final_tuition_fee, 2) . ").");
        }

        if ($discountAmount > (float) $feeAccount->remaining_balance) {
            throw new InvalidArgumentException("Concession cannot exceed the current outstanding balance (₹" . number_format($feeAccount->remaining_balance, 2) . ").");
        }

        // Check for duplicate pending requests
        $duplicate = StudentFeeAdjustment::where('account_id', $accountId)
            ->where('approval_status', 'PENDING')
            ->where('discount_amount', $discountAmount)
            ->exists();

        if ($duplicate) {
            throw new InvalidArgumentException("A pending concession request for this amount already exists for this student.");
        }

        $adjustment = StudentFeeAdjustment::create([
            'account_id' => $accountId,
            'adjustment_type' => $data['adjustment_type'],
            'discount_amount' => $discountAmount,
            'discount_percent' => $data['discount_percent'] ?? 0,
            'reason' => $data['reason'],
            'requested_by' => $requesterId,
            'approval_status' => 'PENDING',
        ]);

        AuditLog::create([
            'user_id' => $requesterId,
            'action' => 'CONCESSION_REQUESTED',
            'table_name' => 'student_fee_adjustments',
            'record_id' => $adjustment->adjustment_id,
            'new_value' => json_encode($data),
            'ip_address' => request()->ip()
        ]);

        return $adjustment;
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

            $oldStatus = $adjustment->approval_status;

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

                $oldWaived = $feeAccount->waived_amount;
                $feeAccount->waived_amount = (float) $feeAccount->waived_amount + (float) $adjustment->discount_amount;
                $feeAccount->recalculateTotals();
                $feeAccount->save();

                AuditLog::create([
                    'user_id' => $deciderId,
                    'action' => 'CONCESSION_APPROVED',
                    'table_name' => 'student_fee_accounts',
                    'record_id' => $feeAccount->account_id,
                    'old_value' => $oldWaived,
                    'new_value' => $feeAccount->waived_amount,
                    'ip_address' => request()->ip()
                ]);
            } else {
                AuditLog::create([
                    'user_id' => $deciderId,
                    'action' => 'CONCESSION_REJECTED',
                    'table_name' => 'student_fee_adjustments',
                    'record_id' => $adjustment->adjustment_id,
                    'old_value' => $oldStatus,
                    'new_value' => 'REJECTED',
                    'ip_address' => request()->ip()
                ]);
            }

            return $adjustment;
        });
    }

    /**
     * Helper to log actions to audit_logs
     */
    protected function logAction(int $userId, string $action, string $newValue, ?string $tableName = null, ?int $recordId = null): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'table_name' => $tableName ?? 'finance',
            'record_id' => $recordId,
            'old_value' => null,
            'new_value' => $newValue,
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
    protected function generateReceiptNumber(?\App\Models\AcademicYear $academicYear = null): string
    {
        $lastReceipt = \App\Models\Receipt::lockForUpdate()
            ->orderByDesc('receipt_id')
            ->first();

        $nextId = $lastReceipt
            ? ($lastReceipt->receipt_id + 1)
            : 1;

        $year = $academicYear && $academicYear->start_date
            ? date('Y', strtotime($academicYear->start_date))
            : date('Y');

        return 'RCP-' . $year . '-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
    }
}