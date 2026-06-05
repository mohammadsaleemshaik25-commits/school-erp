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

                // Create New Fee Account
                StudentFeeAccount::create([
                    'enrollment_id' => $newEnrollment->enrollment_id,
                    'fee_structure_id' => $feeStructure->fee_structure_id,
                    'discount_amount' => 0,
                    'final_tuition_fee' => $feeStructure->tuition_fee,
                    'books_status' => 'PENDING',
                    'books_from_school' => true,
                    'books_fee_applied' => 0,
                    'books_fee' => $feeStructure->books_fee,
                    'net_fee' => $feeStructure->tuition_fee,
                    'previous_balance' => 0,
                    'waived_amount' => 0,
                    'total_due' => $feeStructure->tuition_fee,
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
