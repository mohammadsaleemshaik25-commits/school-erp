<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentFeeAccount;
use App\Models\FeeStructure;
use App\Models\AuditLog;
use App\Models\ClassRoom;
use App\Models\FeeComponent;
use App\Models\ClassFeeComponent;
use App\Models\StudentFeeComponentAccount;
use App\Models\StudentFeeComponentSelection;
use Illuminate\Support\Facades\DB;
use Exception;

class EnrollmentService
{
    /**
     * Create a new student enrollment along with its associated fee accounts.
     *
     * @param int $studentId
     * @param int $academicYearId
     * @param int $classId
     * @param int|null $sectionId
     * @param string $promotionStatus
     * @param string $status
     * @param int $userId
     * @param array $selectedComponents Optional array of component codes to select (e.g., for books decision)
     * @return StudentEnrollment
     * @throws Exception
     */
    public function createEnrollmentWithFees(
        int $studentId,
        int $academicYearId,
        int $classId,
        ?int $sectionId,
        string $promotionStatus,
        string $status,
        int $userId,
        array $selectedComponents = []
    ): StudentEnrollment {
        return DB::transaction(function () use (
            $studentId,
            $academicYearId,
            $classId,
            $sectionId,
            $promotionStatus,
            $status,
            $userId,
            $selectedComponents
        ) {
            // 1. Validate for duplicate active enrollments
            $existingActiveEnrollment = StudentEnrollment::where('student_id', $studentId)
                ->where('academic_year_id', $academicYearId)
                ->where('status', 'ACTIVE')
                ->first();

            if ($existingActiveEnrollment) {
                throw new Exception("An active enrollment already exists for this student in the selected academic year.");
            }

            // 2. Find Fee Structure
            $feeStructure = FeeStructure::where('academic_year_id', $academicYearId)
                ->where('class_id', $classId)
                ->first();

            if (!$feeStructure) {
                throw new Exception("Fee structure not defined for the selected academic year and class.");
            }

            // 3. Create StudentEnrollment
            $enrollment = StudentEnrollment::create([
                'student_id' => $studentId,
                'academic_year_id' => $academicYearId,
                'class_id' => $classId,
                'section_id' => $sectionId,
                'promotion_status' => $promotionStatus,
                'status' => $status,
            ]);

            // 4. Ensure ClassFeeComponents exist (seed if not)
            $this->seedClassFeeComponents($academicYearId, $classId, $feeStructure);

            // 5. Create StudentFeeComponentAccount records and StudentFeeAccount
            $this->createStudentFeeAccountsForEnrollment($enrollment, $feeStructure, $userId, $selectedComponents);

            // 6. Log the action
            AuditLog::create([
                'user_id' => $userId,
                'action' => 'STUDENT_ENROLLMENT_CREATED',
                'table_name' => 'student_enrollments',
                'record_id' => $enrollment->enrollment_id,
                'new_value' => "Enrollment created for student {$studentId} in AY {$academicYearId}, Class {$classId} with fee accounts.",
                'ip_address' => request()->ip(),
            ]);

            return $enrollment;
        });
    }

    /**
     * Helper to seed ClassFeeComponent records if they don't exist.
     *
     * @param int $academicYearId
     * @param int $classId
     * @param FeeStructure $feeStructure
     * @throws Exception
     */
    public function seedClassFeeComponents(int $academicYearId, int $classId, FeeStructure $feeStructure): void
    {
        $classFeeCount = ClassFeeComponent::where('academic_year_id', $academicYearId)
            ->where('class_id', $classId)
            ->count();

        if ($classFeeCount === 0) {
            $tuitionSplit = round((float)$feeStructure->tuition_fee / 3, 2);
            $tuitionRem = (float)$feeStructure->tuition_fee - ($tuitionSplit * 2);

            // Determine admission fee based on class
            $classRoom = ClassRoom::find($classId);
            $className = strtoupper($classRoom->class_name ?? '');
            $admissionFee = 500.00;
            if (preg_match('/VI|VII|VIII|IX|X/', $className) && !preg_match('/NURSERY|LKG|UKG|I|II|III|IV|V/', $className)) {
                $admissionFee = 1000.00;
            }

            $bookTotal = (float)$feeStructure->books_fee;
            $textbookVal = round($bookTotal * 0.90, 2);
            $notebookVal = $bookTotal - $textbookVal;

            $defaultPrices = [
                'ADMISSION' => $admissionFee,
                'TERM1' => $tuitionSplit,
                'TERM2' => $tuitionSplit,
                'TERM3' => $tuitionRem,
                'TEXTBOOK' => $textbookVal,
                'NOTEBOOK' => $notebookVal,
                'EXAM' => 500.00,
                'DIARY' => 500.00,
                'FILE' => 500.00,
                'BELT' => 150.00,
                'TIE' => 100.00,
                'TSHIRT' => 400.00,
            ];

            foreach ($defaultPrices as $code => $amt) {
                $comp = FeeComponent::where('component_code', $code)->first();
                if ($comp) {
                    ClassFeeComponent::create([
                        'academic_year_id' => $academicYearId,
                        'class_id' => $classId,
                        'component_id' => $comp->component_id,
                        'amount' => $amt,
                        'created_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Helper to create StudentFeeComponentAccount records and the main StudentFeeAccount.
     *
     * @param StudentEnrollment $enrollment
     * @param FeeStructure $feeStructure
     * @param int $userId
     * @param array $selectedComponents
     * @throws Exception
     */
    public function createStudentFeeAccountsForEnrollment(
        StudentEnrollment $enrollment,
        FeeStructure $feeStructure,
        int $userId,
        array $selectedComponents = []
    ): void {
        $student = $enrollment->student; // Assuming student relationship is loaded or can be accessed

        // Fallback for empty selected components based on booksStatus string (if applicable)
        // This logic is replicated from AdmissionService::finalizeAdmission
        if (empty($selectedComponents)) {
            // Assuming BOOKS_SCHOOL implies a default set of book components
            $selectedComponents = ['TEXTBOOK', 'NOTEBOOK', 'EXAM', 'DIARY', 'FILE'];
        }

        $classComponents = ClassFeeComponent::with('component')
            ->where('academic_year_id', $enrollment->academic_year_id)
            ->where('class_id', $enrollment->class_id)
            ->get();

        $chargeAdmission = false;
        if ($student->admission_type === 'NEW' || $student->admission_type === 'READMISSION') {
            $chargeAdmission = true;
        } elseif ($student->admission_type === 'TRANSFER') {
            $chargeAdmission = in_array('ADMISSION', $selectedComponents);
        }

        $booksFeeApplied = 0.00;
        $tuitionFeeApplied = 0.00;

        foreach ($classComponents as $classComp) {
            $code = $classComp->component->component_code;
            $category = $classComp->component->category;

            $shouldCharge = false;
            if ($category === 'TUITION') {
                $shouldCharge = true;
            } elseif ($code === 'ADMISSION') {
                $shouldCharge = $chargeAdmission;
            } else {
                $shouldCharge = in_array($code, $selectedComponents);
            }

            if ($shouldCharge) {
                $amount = (float) $classComp->amount;

                StudentFeeComponentAccount::create([
                    'student_id' => $student->student_id,
                    'enrollment_id' => $enrollment->enrollment_id,
                    'component_id' => $classComp->component_id,
                    'amount' => $amount,
                    'concession_amount' => 0.00,
                    'waiver_amount' => 0.00,
                    'paid_amount' => 0.00,
                    'balance_amount' => $amount,
                    'status' => 'PENDING',
                    'created_at' => now(),
                ]);

                if ($category === 'BOOKS') {
                    $booksFeeApplied += $amount;
                    StudentFeeComponentSelection::create([
                        'student_id' => $student->student_id,
                        'enrollment_id' => $enrollment->enrollment_id,
                        'component_id' => $classComp->component_id,
                        'amount' => $amount,
                        'selected_by' => $userId,
                        'selected_at' => now(),
                    ]);
                } elseif ($category === 'STORE') { // STORE items are considered part of other fees, not books.
                    $tuitionFeeApplied += $amount; // Adding to tuitionFeeApplied as they are not book-related.
                    StudentFeeComponentSelection::create([
                        'student_id' => $student->student_id,
                        'enrollment_id' => $enrollment->enrollment_id,
                        'component_id' => $classComp->component_id,
                        'amount' => $amount,
                        'selected_by' => $userId,
                        'selected_at' => now(),
                    ]);
                } else {
                    $tuitionFeeApplied += $amount;
                }
            }
        }

        $legacyBooksStatus = 'OUTSIDE';
        if ($booksFeeApplied > 0) {
            $legacyBooksStatus = 'SCHOOL';
        }

        StudentFeeAccount::create([
            'enrollment_id' => $enrollment->enrollment_id,
            'fee_structure_id' => $feeStructure->fee_structure_id,
            'discount_amount' => 0,
            'final_tuition_fee' => $tuitionFeeApplied,
            'books_status' => $legacyBooksStatus,
            'books_from_school' => ($legacyBooksStatus === 'SCHOOL'),
            'books_decision_by' => $userId,
            'books_decision_date' => now(),
            'books_fee_applied' => $booksFeeApplied,
            'books_fee' => $feeStructure->books_fee,
            'net_fee' => $tuitionFeeApplied + $booksFeeApplied,
            'previous_balance' => 0,
            'waived_amount' => 0,
            'total_due' => $tuitionFeeApplied + $booksFeeApplied,
            'status' => 'UNPAID',
        ]);
    }
}