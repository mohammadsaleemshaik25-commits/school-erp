<?php

namespace App\Services;

use App\Models\StudentFeeAccount;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\AcademicYear;
use App\Models\StudentFeeAdjustment;
use App\Models\AuditLog;
use App\Models\StudentFeeComponentAccount;
use App\Models\PaymentComponentAllocation;
use App\Models\ReceiptComponentDetail;
use App\Models\FeeWaiver;
use App\Models\User;
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
                throw new InvalidArgumentException("Only whole rupee amounts are allowed.");
            }

            // 1. Lock Student Fee Account for updates
            $feeAccount = StudentFeeAccount::where('account_id', $accountId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($amount <= 0) {
                throw new InvalidArgumentException("Payment amount must be greater than zero.");
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

            $isComponentBased = $feeAccount->enrollment->feeComponentAccounts()->count() > 0;

            if ($isComponentBased) {
                if (empty($data['allocations']) || !is_array($data['allocations'])) {
                    throw new InvalidArgumentException("Component-wise payment allocations are required.");
                }

                $allocationSum = array_sum($data['allocations']);
                if (abs($allocationSum - $amount) > 0.001) {
                    throw new InvalidArgumentException("The sum of component allocations (₹" . number_format($allocationSum, 2) . ") must equal the total payment amount (₹" . number_format($amount, 2) . ").");
                }

                $booksPaid = 0.00;
                $tuitionPaid = 0.00;
                $previousPaid = 0.00;
                $allocationsToCreate = [];

                foreach ($data['allocations'] as $compAccId => $allocAmount) {
                    $allocAmount = (float) $allocAmount;
                    if ($allocAmount <= 0) {
                        continue;
                    }

                    $componentAccount = StudentFeeComponentAccount::where('id', $compAccId)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($allocAmount > (float) $componentAccount->balance_amount) {
                        throw new InvalidArgumentException("Payment cannot exceed outstanding balance. Component: " . $componentAccount->component->component_name . " has balance ₹" . number_format($componentAccount->balance_amount, 2) . ", but ₹" . number_format($allocAmount, 2) . " was entered.");
                    }

                    $prevBalance = (float) $componentAccount->balance_amount;

                    // Update component account
                    $componentAccount->paid_amount += $allocAmount;
                    $componentAccount->recalculateBalance();
                    $componentAccount->save();

                    // Group categories for legacy sync
                    $category = $componentAccount->component->category;
                    if ($category === 'BOOKS') {
                        $booksPaid += $allocAmount;
                    } elseif ($category === 'CARRY_FORWARD') {
                        $previousPaid += $allocAmount;
                    } else {
                        $tuitionPaid += $allocAmount;
                    }

                    $allocationsToCreate[] = [
                        'component_account_id' => $componentAccount->id,
                        'amount_paid' => $allocAmount,
                        'component_id' => $componentAccount->component_id,
                        'component_name' => $componentAccount->component->component_name,
                        'previous_balance' => $prevBalance,
                        'remaining_balance' => (float) $componentAccount->balance_amount,
                    ];
                }

                $payment = Payment::create([
                    'account_id' => $feeAccount->account_id,
                    'fee_component_type' => count($allocationsToCreate) > 1 ? 'MIXED' : ($componentAccount->component->category ?? 'TUITION'),
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

                foreach ($allocationsToCreate as $alloc) {
                    PaymentComponentAllocation::create([
                        'payment_id' => $payment->payment_id,
                        'component_account_id' => $alloc['component_account_id'],
                        'amount_paid' => $alloc['amount_paid'],
                    ]);
                }

                // Generate Receipt
                $receiptNumber = $this->generateReceiptNumber($feeAccount->enrollment->academicYear);
                $receipt = Receipt::create([
                    'payment_id' => $payment->payment_id,
                    'receipt_number' => $receiptNumber,
                    'receipt_date' => now(),
                    'generated_datetime' => now(),
                    'generated_by' => $clerkId,
                    'status' => 'ACTIVE',
                ]);

                foreach ($allocationsToCreate as $alloc) {
                    ReceiptComponentDetail::create([
                        'receipt_id' => $receipt->receipt_id,
                        'component_id' => $alloc['component_id'],
                        'component_name' => $alloc['component_name'],
                        'previous_balance' => $alloc['previous_balance'],
                        'paid_amount' => $alloc['amount_paid'],
                        'remaining_balance' => $alloc['remaining_balance'],
                    ]);
                }

                // Sync legacy account values
                $booksFeeApplied = $feeAccount->enrollment->feeComponentAccounts()
                    ->whereHas('component', function($q) {
                        $q->where('category', 'BOOKS');
                    })->sum('amount');

                $tuitionFeeApplied = $feeAccount->enrollment->feeComponentAccounts()
                    ->whereHas('component', function($q) {
                        $q->whereIn('category', ['TUITION', 'ADMISSION', 'STORE']);
                    })->sum('amount');

                $discountAmount = $feeAccount->enrollment->feeComponentAccounts()->sum('concession_amount');
                $waivedAmount = $feeAccount->enrollment->feeComponentAccounts()->sum('waiver_amount');
                $previousBalance = $feeAccount->enrollment->feeComponentAccounts()
                    ->whereHas('component', function($q) {
                        $q->where('category', 'CARRY_FORWARD');
                    })->sum('amount');

                $feeAccount->final_tuition_fee = $tuitionFeeApplied;
                $feeAccount->books_fee_applied = $booksFeeApplied;
                $feeAccount->previous_balance = $previousBalance;
                $feeAccount->discount_amount = $discountAmount;
                $feeAccount->waived_amount = $waivedAmount;
                $feeAccount->recalculateTotals();
                $feeAccount->save();

                $this->logAction(
                    $clerkId,
                    'PAYMENT_COLLECTION',
                    "Collected component payment of ₹{$amount} via {$payment->payment_mode} for Student: {$feeAccount->enrollment->student->student_name}. Receipt: {$receiptNumber}"
                );

                $payment->refresh();
                return $payment->load('receipt', 'feeAccount.enrollment.student');

            } else {
                // Fallback to legacy payments
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

                if ($amount > $totalOutstanding) {
                    throw new InvalidArgumentException("Excessive payment attempted. Outstanding balance is ₹" . number_format($totalOutstanding, 2));
                }

                if ($feeAccount->booksPurchasedOutside()) {
                    if ((float)$feeAccount->books_fee_applied > 0) {
                        $feeAccount->books_fee_applied = 0.00;
                        $feeAccount->recalculateTotals();
                        $feeAccount->save();
                    }
                }

                $allocation = $data['allocation'] ?? 'TUITION';
                $overpaymentAllocation = $data['overpayment_allocation'] ?? 'TUITION';

                $booksRemaining = $feeAccount->remaining_books_balance;
                $tuitionRemaining = $feeAccount->remaining_tuition_balance;

                $booksPaid = 0.00;
                $tuitionPaid = 0.00;
                $previousPaid = 0.00;

                if ($allocation === 'BOOKS') {
                    if ($amount <= $booksRemaining) {
                        $booksPaid = $amount;
                    } else {
                        $booksPaid = $booksRemaining;
                        $remainder = $amount - $booksRemaining;
                        if ($overpaymentAllocation === 'PREVIOUS') {
                            if ($remainder <= $prevRemaining) {
                                $previousPaid = $remainder;
                            } else {
                                $previousPaid = $prevRemaining;
                                $tuitionPaid = $remainder - $prevRemaining;
                            }
                        } else {
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
                        if ($overpaymentAllocation === 'BOOKS') {
                            if ($remainder <= $booksRemaining) {
                                $booksPaid = $remainder;
                            } else {
                                $booksPaid = $booksRemaining;
                                $tuitionPaid = $remainder - $booksRemaining;
                            }
                        } else {
                            if ($remainder <= $tuitionRemaining) {
                                $tuitionPaid = $remainder;
                            } else {
                                $tuitionPaid = $tuitionRemaining;
                                $booksPaid = $remainder - $tuitionRemaining;
                            }
                        }
                    }
                } else {
                    if ($amount <= $tuitionRemaining) {
                        $tuitionPaid = $amount;
                    } else {
                        $tuitionPaid = $tuitionRemaining;
                        $remainder = $amount - $tuitionRemaining;
                        if ($overpaymentAllocation === 'PREVIOUS') {
                            if ($remainder <= $prevRemaining) {
                                $previousPaid = $remainder;
                            } else {
                                $previousPaid = $prevRemaining;
                                $booksPaid = $remainder - $prevRemaining;
                            }
                        } else {
                            if ($remainder <= $booksRemaining) {
                                $booksPaid = $remainder;
                            } else {
                                $booksPaid = $booksRemaining;
                                $previousPaid = $remainder - $booksRemaining;
                            }
                        }
                    }
                }

                if ($feeAccount->booksPurchasedOutside()) {
                    $tuitionPaid += $booksPaid;
                    $booksPaid = 0.00;
                }

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

                $feeAccount->recalculateTotals();
                $feeAccount->save();

                $receiptNumber = $this->generateReceiptNumber($feeAccount->enrollment->academicYear);
                $receipt = Receipt::create([
                    'payment_id' => $payment->payment_id,
                    'receipt_number' => $receiptNumber,
                    'receipt_date' => now(),
                    'generated_datetime' => now(),
                    'generated_by' => $clerkId,
                    'status' => 'ACTIVE',
                ]);

                $this->logAction(
                    $clerkId,
                    'PAYMENT_COLLECTION',
                    "Collected payment of ₹{$amount} via {$payment->payment_mode} for Student Fee Account ID: {$feeAccount->account_id}. Receipt: {$receiptNumber}"
                );

                $payment->refresh();
                return $payment->load('receipt', 'feeAccount.enrollment.student');
            }
        });
    }

    /**
     * Safely cancel a payment. Restores the student's fee dues and flags records.
     * Payments are never deleted.
     */
    public function cancelPayment(int $paymentId, string $reason, int $userId): Payment
    {
        return DB::transaction(function () use ($paymentId, $reason, $userId) {
            $payment = Payment::with('allocations')->where('payment_id', $paymentId)
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
                    'cancelled_by' => $userId,
                    'cancelled_at' => now(),
                ]);
            }

            // 3. Mark Payment Cancelled
            $payment->update([
                'status' => 'CANCELLED',
            ]);

            // Reverse component-based allocations if they exist
            if ($payment->allocations && $payment->allocations->count() > 0) {
                foreach ($payment->allocations as $alloc) {
                    $compAccount = $alloc->componentAccount;
                    if ($compAccount) {
                        $compAccount->paid_amount = max(0.00, $compAccount->paid_amount - $alloc->amount_paid);
                        $compAccount->recalculateBalance();
                        $compAccount->save();
                    }
                }

                // Sync legacy student fee account totals
                $booksFeeApplied = $feeAccount->enrollment->feeComponentAccounts()
                    ->whereHas('component', function($q) {
                        $q->where('category', 'BOOKS');
                    })->sum('amount');

                $tuitionFeeApplied = $feeAccount->enrollment->feeComponentAccounts()
                    ->whereHas('component', function($q) {
                        $q->whereIn('category', ['TUITION', 'ADMISSION', 'STORE']);
                    })->sum('amount');

                $discountAmount = $feeAccount->enrollment->feeComponentAccounts()->sum('concession_amount');
                $waivedAmount = $feeAccount->enrollment->feeComponentAccounts()->sum('waiver_amount');
                $previousBalance = $feeAccount->enrollment->feeComponentAccounts()
                    ->whereHas('component', function($q) {
                        $q->where('category', 'CARRY_FORWARD');
                    })->sum('amount');

                $feeAccount->final_tuition_fee = $tuitionFeeApplied;
                $feeAccount->books_fee_applied = $booksFeeApplied;
                $feeAccount->previous_balance = $previousBalance;
                $feeAccount->discount_amount = $discountAmount;
                $feeAccount->waived_amount = $waivedAmount;
            } else {
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
        $componentId = $data['component_id'] ?? null;

        if ($discountAmount <= 0) {
            throw new InvalidArgumentException("Concession amount must be greater than zero.");
        }

        $feeAccount = StudentFeeAccount::where('account_id', $accountId)->firstOrFail();

        if ($componentId) {
            $compAccount = StudentFeeComponentAccount::where('enrollment_id', $feeAccount->enrollment_id)
                ->where('component_id', $componentId)
                ->firstOrFail();

            if ($discountAmount > (float) $compAccount->amount) {
                throw new InvalidArgumentException("Concession cannot exceed the total component fee (₹" . number_format($compAccount->amount, 2) . ").");
            }

            if ($discountAmount > (float) $compAccount->balance_amount) {
                throw new InvalidArgumentException("Concession cannot exceed the current outstanding component balance (₹" . number_format($compAccount->balance_amount, 2) . ").");
            }
        } else {
            if ($discountAmount > (float) $feeAccount->final_tuition_fee) {
                throw new InvalidArgumentException("Concession cannot exceed the total tuition fee (₹" . number_format($feeAccount->final_tuition_fee, 2) . ").");
            }

            if ($discountAmount > (float) $feeAccount->remaining_balance) {
                throw new InvalidArgumentException("Concession cannot exceed the current outstanding balance (₹" . number_format($feeAccount->remaining_balance, 2) . ").");
            }
        }

        // Check for duplicate pending requests
        $duplicateQuery = StudentFeeAdjustment::where('account_id', $accountId)
            ->where('approval_status', 'PENDING')
            ->where('discount_amount', $discountAmount);
        
        if ($componentId) {
            $duplicateQuery->where('component_id', $componentId);
        } else {
            $duplicateQuery->whereNull('component_id');
        }

        if ($duplicateQuery->exists()) {
            throw new InvalidArgumentException("A pending concession request for this amount already exists for this student.");
        }

        $adjustment = StudentFeeAdjustment::create([
            'account_id' => $accountId,
            'component_id' => $componentId,
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

                if ($feeAccount->enrollment->feeComponentAccounts()->count() > 0 && $adjustment->component_id) {
                    $compAccount = StudentFeeComponentAccount::where('enrollment_id', $feeAccount->enrollment_id)
                        ->where('component_id', $adjustment->component_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $compAccount->concession_amount += (float) $adjustment->discount_amount;
                    $compAccount->recalculateBalance();
                    $compAccount->save();

                    // Sync legacy fields
                    $discountAmount = $feeAccount->enrollment->feeComponentAccounts()->sum('concession_amount');
                    $feeAccount->discount_amount = $discountAmount;
                    $feeAccount->recalculateTotals();
                    $feeAccount->save();

                    $newValue = "Component Concession: " . $compAccount->component->component_name . " (₹" . $adjustment->discount_amount . ")";
                } else {
                    $feeAccount->waived_amount = (float) $feeAccount->waived_amount + (float) $adjustment->discount_amount;
                    $feeAccount->recalculateTotals();
                    $feeAccount->save();
                    $newValue = $feeAccount->waived_amount;
                }

                AuditLog::create([
                    'user_id' => $deciderId,
                    'action' => 'CONCESSION_APPROVED',
                    'table_name' => 'student_fee_accounts',
                    'record_id' => $feeAccount->account_id,
                    'old_value' => $oldWaived,
                    'new_value' => $newValue,
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
     * Cancel a receipt and its associated payment.
     */
    public function cancelReceipt(int $receiptId, string $reason, int $userId): Receipt
    {
        return DB::transaction(function () use ($receiptId, $reason, $userId) {
            $receipt = Receipt::where('receipt_id', $receiptId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($receipt->status === 'CANCELLED') {
                throw new InvalidArgumentException("This receipt is already cancelled.");
            }

            // Cancel the associated payment (this handles balance restoration)
            $this->cancelPayment($receipt->payment_id, $reason, $userId);

            // Double check receipt status (cancelPayment should have updated it, but let's be sure)
            $receipt->refresh();
            if ($receipt->status !== 'CANCELLED') {
                $receipt->update([
                    'status' => 'CANCELLED',
                    'cancellation_reason' => $reason,
                ]);
            }

            return $receipt;
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

    public function applyWaiver(array $data, int $userId): void
    {
        DB::transaction(function () use ($data, $userId) {
            $studentId = $data['student_id'];
            $enrollmentId = $data['enrollment_id'];
            $componentId = $data['component_id'];
            $waiverAmount = (float) $data['waiver_amount'];
            $reason = $data['reason'];

            $user = User::findOrFail($userId);
            $role = strtoupper(optional($user->role)->role_name ?? '');
            if (!in_array($role, ['PRINCIPAL', 'CORRESPONDENT', 'ADMIN', 'ADMINISTRATOR'])) {
                throw new Exception("Unauthorized. Only Principal and Correspondent can approve waivers.");
            }

            $feeAccount = StudentFeeComponentAccount::where('enrollment_id', $enrollmentId)
                ->where('component_id', $componentId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($waiverAmount <= 0) {
                throw new InvalidArgumentException("Waiver amount must be greater than zero.");
            }

            $currentWaiverable = $feeAccount->amount - $feeAccount->concession_amount - $feeAccount->paid_amount - $feeAccount->waiver_amount;
            if ($waiverAmount > $currentWaiverable) {
                throw new InvalidArgumentException("Waiver amount cannot exceed outstanding balance of ₹" . number_format($currentWaiverable, 2));
            }

            $waiver = FeeWaiver::create([
                'student_id' => $studentId,
                'enrollment_id' => $enrollmentId,
                'component_id' => $componentId,
                'waiver_amount' => $waiverAmount,
                'reason' => $reason,
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);

            $feeAccount->waiver_amount += $waiverAmount;
            $feeAccount->recalculateBalance();
            $feeAccount->save();

            $legacyAccount = StudentFeeAccount::where('enrollment_id', $enrollmentId)
                ->lockForUpdate()
                ->firstOrFail();
            $legacyAccount->waived_amount = $legacyAccount->enrollment->feeComponentAccounts()->sum('waiver_amount');
            $legacyAccount->recalculateTotals();
            $legacyAccount->save();

            AuditLog::create([
                'user_id' => $userId,
                'action' => 'FEE_WAIVER_APPROVED',
                'table_name' => 'fee_waivers',
                'record_id' => $waiver->waiver_id,
                'new_value' => "Approved waiver of ₹{$waiverAmount} on component: {$feeAccount->component->component_name} for Student ID: {$studentId}. Reason: {$reason}",
                'ip_address' => request()->ip(),
            ]);
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