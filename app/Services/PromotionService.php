<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentFeeAccount;
use App\Models\FeeStructure;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Exception;

class PromotionService
{
    /**
     * Promote a single student
     */
    public function promoteStudent(array $data, int $userId): void
    {
        DB::transaction(function () use ($data, $userId) {
            $studentId = $data['student_id'];
            $oldEnrollmentId = $data['old_enrollment_id'];
            $status = strtoupper($data['status']); // PROMOTED, DETAINED, TRANSFERRED, DROPPED
            
            $oldEnrollment = StudentEnrollment::findOrFail($oldEnrollmentId);
            
            // 1. Update old enrollment status
            $oldEnrollment->update([
                'promotion_status' => $status,
                'status' => ($status === 'PROMOTED' || $status === 'DETAINED') ? 'INACTIVE' : 'DROPPED'
            ]);

            // 2. Handle PROMOTED or DETAINED (New Enrollment needed)
            if ($status === 'PROMOTED' || $status === 'DETAINED') {
                $targetAYId = $data['target_academic_year_id'];
                $targetClassId = $data['target_class_id'];
                $targetSectionId = $data['target_section_id'];

                // Find Fee Structure for target
                $feeStructure = FeeStructure::where('academic_year_id', $targetAYId)
                    ->where('class_id', $targetClassId)
                    ->first();

                if (!$feeStructure) {
                    throw new Exception("Fee structure not defined for target Academic Year and Class.");
                }

                // Create New Enrollment
                $newEnrollment = StudentEnrollment::create([
                    'student_id' => $studentId,
                    'academic_year_id' => $targetAYId,
                    'class_id' => $targetClassId,
                    'section_id' => $targetSectionId,
                    'promotion_status' => $status === 'PROMOTED' ? 'PROMOTED' : 'DETAINED',
                    'status' => 'ACTIVE'
                ]);

                // Calculate previous dues by category
                $prevTuitionDue = 0.00;
                $prevBooksDue = 0.00;
                $prevAdmissionDue = 0.00;

                $oldCompAccounts = $oldEnrollment->feeComponentAccounts;
                foreach ($oldCompAccounts as $oldCompAcc) {
                    $bal = (float)$oldCompAcc->balance_amount;
                    if ($bal <= 0) {
                        continue;
                    }
                    $category = $oldCompAcc->component->category;
                    $code = $oldCompAcc->component->component_code;

                    if ($category === 'BOOKS' || $code === 'PREV_BOOKS_DUE') {
                        $prevBooksDue += $bal;
                    } elseif ($code === 'ADMISSION' || $code === 'PREV_ADMISSION_DUE') {
                        $prevAdmissionDue += $bal;
                    } else {
                        // TUITION, STORE, PREV_TUITION_DUE, etc.
                        $prevTuitionDue += $bal;
                    }
                }

                $totalPrevDue = $prevTuitionDue + $prevBooksDue + $prevAdmissionDue;

                // Ensure class_fee_components exist for target class/year
                $classFeeCount = \App\Models\ClassFeeComponent::where('academic_year_id', $targetAYId)
                    ->where('class_id', $targetClassId)
                    ->count();
                if ($classFeeCount === 0) {
                    $this->enrollmentService->seedClassFeeComponents($targetAYId, $targetClassId, $feeStructure);
                }

                // Create new mandatory components (TERM1, TERM2, TERM3)
                $classComponents = \App\Models\ClassFeeComponent::with('component')
                    ->where('academic_year_id', $targetAYId)
                    ->where('class_id', $targetClassId)
                    ->whereHas('component', function($q) {
                        $q->where('category', 'TUITION');
                    })
                    ->get();

                $newTuitionAmount = 0.00;
                foreach ($classComponents as $classComp) {
                    $amount = (float)$classComp->amount;
                    \App\Models\StudentFeeComponentAccount::create([
                        'student_id' => $studentId,
                        'enrollment_id' => $newEnrollment->enrollment_id,
                        'component_id' => $classComp->component_id,
                        'amount' => $amount,
                        'concession_amount' => 0.00,
                        'waiver_amount' => 0.00,
                        'paid_amount' => 0.00,
                        'balance_amount' => $amount,
                        'status' => 'PENDING',
                        'created_at' => now(),
                    ]);
                    $newTuitionAmount += $amount;
                }

                // Create Carry Forward Components if outstanding dues exist
                $carryForwards = [
                    'PREV_TUITION_DUE' => $prevTuitionDue,
                    'PREV_BOOKS_DUE' => $prevBooksDue,
                    'PREV_ADMISSION_DUE' => $prevAdmissionDue,
                ];

                foreach ($carryForwards as $code => $amt) {
                    if ($amt > 0) {
                        $comp = \App\Models\FeeComponent::where('component_code', $code)->first();
                        if ($comp) {
                            \App\Models\StudentFeeComponentAccount::create([
                                'student_id' => $studentId,
                                'enrollment_id' => $newEnrollment->enrollment_id,
                                'component_id' => $comp->component_id,
                                'amount' => $amt,
                                'concession_amount' => 0.00,
                                'waiver_amount' => 0.00,
                                'paid_amount' => 0.00,
                                'balance_amount' => $amt,
                                'status' => 'PENDING',
                                'created_at' => now(),
                            ]);
                        }
                    }
                }

                // Create New Fee Account
                StudentFeeAccount::create([
                    'enrollment_id' => $newEnrollment->enrollment_id,
                    'fee_structure_id' => $feeStructure->fee_structure_id,
                    'discount_amount' => 0,
                    'final_tuition_fee' => $newTuitionAmount,
                    'books_status' => 'PENDING',
                    'books_from_school' => true,
                    'books_fee_applied' => 0,
                    'books_fee' => $feeStructure->books_fee,
                    'net_fee' => $newTuitionAmount,
                    'previous_balance' => $totalPrevDue,
                    'waived_amount' => 0,
                    'total_due' => $newTuitionAmount + $totalPrevDue,
                    'status' => 'UNPAID'
                ]);
            }

            // 3. Log Audit
            AuditLog::create([
                'user_id' => $userId,
                'action' => 'STUDENT_PROMOTED',
                'table_name' => 'student_enrollments',
                'record_id' => $oldEnrollmentId,
                'old_value' => "Status: {$oldEnrollment->promotion_status}, Class: {$oldEnrollment->class_id}",
                'new_value' => "Promoted as: {$status} to AY: " . ($data['target_academic_year_id'] ?? 'N/A'),
                'ip_address' => request()->ip()
            ]);
        });
    }

    /**
     * Bulk promote students
     */
    public function bulkPromote(array $studentIds, array $commonData, int $userId): array
    {
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($studentIds as $studentId) {
            try {
                $oldEnrollment = StudentEnrollment::where('student_id', $studentId)
                    ->where('status', 'ACTIVE')
                    ->first();

                if (!$oldEnrollment) {
                    throw new Exception("Active enrollment not found for Student ID: {$studentId}");
                }

                $promotionData = array_merge($commonData, [
                    'student_id' => $studentId,
                    'old_enrollment_id' => $oldEnrollment->enrollment_id,
                ]);

                $this->promoteStudent($promotionData, $userId);
                $results['success']++;
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Student ID {$studentId}: " . $e->getMessage();
            }
        }

        return $results;
    }
}
