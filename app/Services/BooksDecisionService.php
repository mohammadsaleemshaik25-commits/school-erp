<?php

namespace App\Services;

use App\Models\StudentFeeAccount;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Exception;
use InvalidArgumentException;

class BooksDecisionService
{
    /**
     * Update the books purchase decision for a student.
     */
    public function updateDecision(int $accountId, string $newStatus, int $userId, string $ipAddress): StudentFeeAccount
    {
        
        return DB::transaction(function () use ($accountId, $newStatus, $userId, $ipAddress) {
            $account = StudentFeeAccount::with('enrollment.student')
                ->where('account_id', $accountId)
                ->lockForUpdate()
                ->firstOrFail();

            

            $oldStatus = $account->books_status;
            $studentName = $account->enrollment->student->student_name;

            // 1. Validation Logic
            $this->validateTransition($account, $newStatus, $userId);

            // 2. Apply Decision Logic
            if ($newStatus === StudentFeeAccount::BOOKS_SCHOOL) {
                $account->books_fee_applied = (float) $account->books_fee;
                $account->books_from_school = true;
            } elseif ($newStatus === StudentFeeAccount::BOOKS_OUTSIDE) {
                $account->books_fee_applied = 0;
                $account->books_from_school = false;
            }

            $account->books_status = $newStatus;
            $account->books_decision_by = $userId;
            $account->books_decision_date = now();

            // 3. Recalculate Totals
            $account->recalculateTotals();
            
            $account->save();

$this->logAction(
    $userId,
    $account,
    $oldStatus,
    $newStatus,
    $studentName,
    $ipAddress
);

            return $account;
        });
    }

    /**
     * Validate if the status transition is allowed.
     */
    protected function validateTransition(StudentFeeAccount $account, string $newStatus, int $userId): void
    {
        $oldStatus = $account->books_status;

        // If status is the same, no need to change
        if ($oldStatus === $newStatus) {
            throw new InvalidArgumentException("The books status is already set to {$newStatus}.");
        }

        // Check if user is Admin (can override)
        // Assuming we have a way to check role. Let's use a simple check for now.
        $isAdmin = auth()->user()->role && in_array(strtoupper(auth()->user()->role->role_name), ['ADMIN', 'ADMINISTRATOR']);

        // BLOCKED: BOOKS_PAID is locked for non-admins
        if ($oldStatus === StudentFeeAccount::BOOKS_PAID && !$isAdmin) {
            throw new Exception("This record is locked because books have already been paid for. Only an Administrator can override this.");
        }

        // BLOCKED: OUTSIDE -> SCHOOL/PENDING for non-admins
        if ($oldStatus === StudentFeeAccount::BOOKS_OUTSIDE && in_array($newStatus, [StudentFeeAccount::BOOKS_SCHOOL, StudentFeeAccount::BOOKS_PENDING]) && !$isAdmin) {
            throw new Exception("Status 'OUTSIDE' cannot be changed back to 'SCHOOL' or 'PENDING' by a Clerk. Please contact Admin.");
        }

        // BLOCKED: Any change if books payments exist (for non-admins)
        if ($account->hasBooksPayments() && !$isAdmin) {
            throw new Exception("Cannot change books status because payments have already been recorded for books. Contact Admin for corrections.");
        }

        // Allowed transitions for Clerks
        $allowedForClerks = [
            StudentFeeAccount::BOOKS_PENDING => [StudentFeeAccount::BOOKS_SCHOOL, StudentFeeAccount::BOOKS_OUTSIDE]
        ];

        if (!$isAdmin) {
            if (!isset($allowedForClerks[$oldStatus]) || !in_array($newStatus, $allowedForClerks[$oldStatus])) {
                throw new Exception("Unauthorized status transition from {$oldStatus} to {$newStatus}.");
            }
        }
    }

    /**
     * Log the status change to audit_logs.
     */
    protected function logAction(int $userId, StudentFeeAccount $account, string $oldStatus, string $newStatus, string $studentName, string $ipAddress): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'action' => 'BOOKS_STATUS_CHANGED',
            'table_name' => 'student_fee_accounts',
            'record_id' => $account->account_id,
            'old_value' => $oldStatus,
            'new_value' => "Status changed to {$newStatus} for student: {$studentName}",
            'ip_address' => $ipAddress,
        ]);
    }
}
