<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentFeeAccount;
use App\Models\FeeStructure;
use App\Models\AuditLog;
use App\Models\StudentDocument;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Exception;

class AdmissionService
{
    /**
     * Create a new admission (Initial Step: PENDING)
     */
    public function createAdmission(array $data, int $userId): Admission
    {
        return DB::transaction(function () use ($data, $userId) {
            // 1. Generate Admission Number
            $admissionNo = $this->generateAdmissionNumber();

            // 2. Handle Photo Upload (Optional)
            $photoPath = null;
            if (isset($data['cropped_photo_path'])) {
                $photoPath = $data['cropped_photo_path'];
            } elseif (isset($data['photo']) && $data['photo']->isValid()) {
                $photoPath = $data['photo']->store('students/photos', 'public');
            }

            // 3. Create Student Record (Basic Info)
            $student = Student::create([
                'admission_no' => $admissionNo,
                'student_name' => $data['student_name'],
                'dob' => $data['dob'],
                'gender' => $data['gender'],
                'nationality' => $data['nationality'] ?? 'Indian',
                'father_name' => $data['father_name'],
                'mother_name' => $data['mother_name'],
                'guardian_name' => $data['guardian_name'] ?? null,
                'pen_no' => $data['pen_no'],
                'aadhaar_no' => $data['aadhaar_no'],
                'phone_primary' => $data['phone_primary'],
                'phone_secondary' => $data['phone_secondary'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'],
                'permanent_address' => $data['permanent_address'] ?? null,
                'village' => $data['village'] ?? null,
                'district' => $data['district'] ?? null,
                'state' => $data['state'] ?? null,
                'pin_code' => $data['pin_code'] ?? null,
                'religion' => $data['religion'] ?? null,
                'category' => $data['category'] ?? null,
                'blood_group' => $data['blood_group'] ?? null,
                'occupation' => $data['occupation'] ?? null,
                'annual_income' => $data['annual_income'] ?? null,
                'previous_school' => $data['previous_school'] ?? null,
                'previous_class' => $data['previous_class'] ?? null,
                'admission_date' => $data['admission_date'],
                'photo_path' => $photoPath,
                'status' => 'INACTIVE', // Becomes ACTIVE after approval and fee account creation
            ]);

            // 4. Create Admission Record (Status: PENDING)
            $admissionStatus = $data['admission_status'] ?? Admission::STATUS_SUBMITTED;
            $admission = Admission::create([
                'student_id' => $student->student_id,
                'academic_year_id' => $data['academic_year_id'],
                'class_id' => $data['class_id'],
                'section_id' => $data['section_id'],
                'admission_status' => $admissionStatus,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
            ]);

            // 5. Handle Document Uploads (Optional)
            if (isset($data['documents']) && is_array($data['documents'])) {
                foreach ($data['documents'] as $docType => $file) {
                    if ($file && $file->isValid()) {
                        $filePath = $file->store('students/documents', 'public');
                        StudentDocument::create([
                            'student_id' => $student->student_id,
                            'document_type' => $docType,
                            'file_name' => $file->getClientOriginalName(),
                            'file_path' => $filePath,
                            'uploaded_at' => now(),
                        ]);
                    }
                }
            }

            // 6. Create Audit Log
            $this->logAction(
                $userId,
                'ADMISSION_CREATED',
                'admissions',
                $admission->admission_id,
                null,
                "Created pending admission for student: {$student->student_name} (ID: {$student->student_id})"
            );

            return $admission;
        });
    }

    /**
     * Verify admission documents (Management Action)
     */
    public function verifyAdmission(int $admissionId, int $userId): Admission
    {
        return DB::transaction(function () use ($admissionId, $userId) {
            $admission = Admission::with('student.documents')->findOrFail($admissionId);

            if (!in_array($admission->admission_status, [Admission::STATUS_DRAFT, Admission::STATUS_SUBMITTED])) {
                throw new Exception("Only DRAFT or SUBMITTED admissions can be verified.");
            }

            // Check mandatory documents
            if (!$admission->student->hasMandatoryDocuments()) {
                throw new Exception("Cannot verify admission: Mandatory documents (Photo, Student Aadhaar) must be uploaded and not rejected.");
            }

            // Mark all documents as verified
            foreach ($admission->student->documents as $document) {
                $document->update([
                    'verification_status' => StudentDocument::STATUS_VERIFIED,
                    'verified_at' => now(),
                    'verified_by' => $userId,
                ]);
            }

            $admission->update([
                'admission_status' => Admission::STATUS_VERIFIED,
                'verified_at' => now(),
                'verified_by' => $userId,
            ]);

            $this->logAction(
                $userId,
                'ADMISSION_VERIFIED',
                'admissions',
                $admissionId,
                $admission->admission_status,
                Admission::STATUS_VERIFIED
            );

            return $admission;
        });
    }

    /**
     * Approve a pending admission.
     */
    public function approveAdmission(int $admissionId, int $userId): Admission
    {
        return DB::transaction(function () use ($admissionId, $userId) {
            $admission = Admission::with('student.documents')->findOrFail($admissionId);

            if (!in_array($admission->admission_status, [Admission::STATUS_VERIFIED, Admission::STATUS_SUBMITTED, 'PENDING'])) {
                throw new Exception("Only verified or submitted admissions can be approved.");
            }

            // Check mandatory documents
            if (!$admission->student->hasMandatoryDocuments()) {
                throw new Exception("Cannot approve admission: Mandatory documents (Photo, Student Aadhaar) must be uploaded and not rejected.");
            }

            $admission->update([
                'admission_status' => Admission::STATUS_APPROVED,
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);

            $this->logAction(
                $userId,
                'ADMISSION_APPROVED',
                'admissions',
                $admissionId,
                $admission->admission_status,
                Admission::STATUS_APPROVED
            );

            return $admission;
        });
    }

    /**
     * Admit student (Finalize admission)
     */
    public function admitStudent(int $admissionId, int $userId): Admission
    {
        return DB::transaction(function () use ($admissionId, $userId) {
            $admission = Admission::with('student')->findOrFail($admissionId);

            if ($admission->admission_status !== Admission::STATUS_APPROVED) {
                throw new Exception("Only approved admissions can be admitted.");
            }

            // Check if enrollment already exists
            $existingEnrollment = StudentEnrollment::where('student_id', $admission->student_id)
                ->where('academic_year_id', $admission->academic_year_id)
                ->first();

            if (!$existingEnrollment) {
                // Create enrollment
                StudentEnrollment::create([
                    'student_id' => $admission->student_id,
                    'academic_year_id' => $admission->academic_year_id,
                    'class_id' => $admission->class_id,
                    'section_id' => $admission->section_id,
                    'promotion_status' => 'NEW',
                    'status' => 'ACTIVE',
                ]);
            }

            // Activate student
            $admission->student->update(['status' => 'ACTIVE']);

            $admission->update([
                'admission_status' => Admission::STATUS_ADMITTED,
                'admitted_at' => now(),
                'admitted_by' => $userId,
            ]);

            $this->logAction(
                $userId,
                'ADMISSION_ADMITTED',
                'admissions',
                $admissionId,
                $admission->admission_status,
                Admission::STATUS_ADMITTED
            );

            return $admission;
        });
    }

    /**
     * Reject admission (Management Action)
     */
    public function rejectAdmission(int $admissionId, int $userId, string $reason): Admission
    {
        return DB::transaction(function () use ($admissionId, $userId, $reason) {
            $admission = Admission::with('student')->findOrFail($admissionId);

            if (in_array($admission->admission_status, [Admission::STATUS_ADMITTED, 'PENDING'])) {
                throw new Exception("Cannot reject admitted or pending admissions.");
            }

            $admission->update([
                'admission_status' => Admission::STATUS_REJECTED,
                'remarks' => $reason,
            ]);

            // Deactivate student if not already active
            if ($admission->student->status === 'INACTIVE') {
                $admission->student->update(['status' => 'INACTIVE']);
            }

            $this->logAction(
                $userId,
                'ADMISSION_REJECTED',
                'admissions',
                $admissionId,
                $admission->admission_status,
                Admission::STATUS_REJECTED . " - Reason: " . $reason
            );

            return $admission;
        });
    }

    /**
     * Finalize admission after Books Decision (Creates Enrollment and Fee Account)
     */
    public function finalizeAdmission(int $admissionId, string $booksStatus, int $userId): Admission
    {
        return DB::transaction(function () use ($admissionId, $booksStatus, $userId) {
            $admission = Admission::with('student')->findOrFail($admissionId);
            
            if ($admission->admission_status !== 'APPROVED') {
                throw new Exception("Admission must be approved before finalization.");
            }

            // Check if already finalized
            if (StudentEnrollment::where('student_id', $admission->student_id)
                ->where('academic_year_id', $admission->academic_year_id)
                ->exists()) {
                throw new Exception("Admission is already finalized (Enrollment exists).");
            }

            // 1. Find Fee Structure
            $feeStructure = FeeStructure::where('academic_year_id', $admission->academic_year_id)
                ->where('class_id', $admission->class_id)
                ->first();

            if (!$feeStructure) {
                throw new Exception("Fee structure not defined for the selected academic year and class.");
            }

            // 2. Create Enrollment
            $enrollment = StudentEnrollment::create([
                'student_id' => $admission->student_id,
                'academic_year_id' => $admission->academic_year_id,
                'class_id' => $admission->class_id,
                'section_id' => $admission->section_id,
                'promotion_status' => 'NEW',
                'status' => 'ACTIVE',
            ]);

            // 3. Determine Books Fee Applied
            $booksFeeApplied = 0;
            $booksFromSchool = false;
            if ($booksStatus === StudentFeeAccount::BOOKS_SCHOOL) {
                $booksFeeApplied = $feeStructure->books_fee;
                $booksFromSchool = true;
            }

            // 4. Create Student Fee Account
            StudentFeeAccount::create([
                'enrollment_id' => $enrollment->enrollment_id,
                'fee_structure_id' => $feeStructure->fee_structure_id,
                'discount_amount' => 0,
                'final_tuition_fee' => $feeStructure->tuition_fee,
                'books_status' => $booksStatus,
                'books_from_school' => $booksFromSchool,
                'books_decision_by' => $userId,
                'books_decision_date' => now(),
                'books_fee_applied' => $booksFeeApplied,
                'books_fee' => $feeStructure->books_fee,
                'net_fee' => $feeStructure->tuition_fee + $booksFeeApplied,
                'previous_balance' => 0,
                'waived_amount' => 0,
                'total_due' => $feeStructure->tuition_fee + $booksFeeApplied,
                'status' => 'UNPAID',
            ]);

            // 5. Activate Student
            $admission->student->update(['status' => 'ACTIVE']);

            $this->logAction(
                $userId,
                'ADMISSION_FINALIZED',
                'admissions',
                $admissionId,
                null,
                "Admission finalized with Books Decision: {$booksStatus}. Fee account created."
            );

            return $admission;
        });
    }

    /**
     * Update an admission (only specific fields allowed).
     */
    public function updateAdmission(Admission $admission, array $data, int $userId): void
    {
        DB::transaction(function () use ($admission, $data, $userId) {
            $student = $admission->student;
            $oldValues = $student->only(['student_name', 'dob', 'father_name', 'mother_name', 'guardian_name', 'phone_primary', 'phone_secondary', 'email', 'address']);

            // Update allowed student fields
            $student->update([
                'student_name' => $data['student_name'],
                'dob' => $data['dob'],
                'father_name' => $data['father_name'],
                'mother_name' => $data['mother_name'],
                'guardian_name' => $data['guardian_name'] ?? null,
                'phone_primary' => $data['phone_primary'],
                'phone_secondary' => $data['phone_secondary'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'],
            ]);

            // Log student information update
            $newValues = $student->only(['student_name', 'dob', 'father_name', 'mother_name', 'guardian_name', 'phone_primary', 'phone_secondary', 'email', 'address']);
            $this->logAction($userId, 'STUDENT_INFO_UPDATED', 'students', $student->student_id, json_encode($oldValues), json_encode($newValues));

            // Handle Photo Replacement
            if (isset($data['photo']) && $data['photo']->isValid()) {
                $oldPhotoPath = $student->photo_path;
                $newPhotoPath = $data['photo']->store('students/photos', 'public');
                
                $student->update(['photo_path' => $newPhotoPath]);

                if ($oldPhotoPath) {
                    Storage::disk('public')->delete($oldPhotoPath);
                }

                $this->logAction($userId, 'PHOTO_UPDATED', 'students', $student->student_id, $oldPhotoPath, $newPhotoPath);
            }

            // Handle Document Management
            if (isset($data['documents']) && is_array($data['documents'])) {
                foreach ($data['documents'] as $docType => $file) {
                    if ($file && $file->isValid()) {
                        $existingDoc = StudentDocument::where('student_id', $student->student_id)
                            ->where('document_type', $docType)
                            ->first();

                        $newPath = $file->store('students/documents', 'public');
                        $fileName = $file->getClientOriginalName();

                        if ($existingDoc) {
                            $oldPath = $existingDoc->file_path;
                            $existingDoc->update([
                                'file_name' => $fileName,
                                'file_path' => $newPath,
                                'uploaded_at' => now(),
                            ]);
                            Storage::disk('public')->delete($oldPath);
                            $this->logAction($userId, 'DOCUMENT_UPDATED', 'student_documents', $existingDoc->document_id, $oldPath, $newPath);
                        } else {
                            $newDoc = StudentDocument::create([
                                'student_id' => $student->student_id,
                                'document_type' => $docType,
                                'file_name' => $fileName,
                                'file_path' => $newPath,
                                'uploaded_at' => now(),
                            ]);
                            $this->logAction($userId, 'DOCUMENT_UPLOADED', 'student_documents', $newDoc->document_id, null, $newPath);
                        }
                    }
                }
            }

            // Update admission remarks if provided
            if (isset($data['remarks'])) {
                $admission->update(['remarks' => $data['remarks']]);
            }

            // Create Audit Log for General Update
            $this->logAction(
                $userId,
                'ADMISSION_UPDATED',
                'admissions',
                $admission->admission_id,
                json_encode($oldValues),
                json_encode($student->only(array_keys($oldValues)))
            );
        });
    }

    /**
     * Delete a specific student document
     */
    public function deleteDocument(int $documentId, int $userId): void
    {
        DB::transaction(function () use ($documentId, $userId) {
            $doc = StudentDocument::findOrFail($documentId);
            $filePath = $doc->file_path;
            
            Storage::disk('public')->delete($filePath);
            $doc->delete();

            $this->logAction($userId, 'DOCUMENT_DELETED', 'student_documents', $documentId, $filePath, null);
        });
    }

    /**
     * Delete an admission and all related student data (Admin only)
     */
    public function deleteAdmission(int $admissionId, int $userId): void
    {
        DB::transaction(function () use ($admissionId, $userId) {
            $admission = Admission::with(['student.enrollments.feeAccount', 'student.documents'])->findOrFail($admissionId);
            $student = $admission->student;

            // 1. Check for payments - prevent delete if financial history exists
            $hasPayments = Payment::whereHas('feeAccount.enrollment', function($q) use ($student) {
                $q->where('student_id', $student->student_id);
            })->where('status', 'SUCCESS')->exists();

            if ($hasPayments) {
                throw new Exception("Cannot delete student record because successful fee payments exist in the ledger. Cancel payments first or mark student as TRANSFERRED.");
            }

            // 2. Delete Documents & Files
            foreach ($student->documents as $doc) {
                Storage::disk('public')->delete($doc->file_path);
                $doc->delete();
            }

            // 3. Delete Photo
            if ($student->photo_path) {
                Storage::disk('public')->delete($student->photo_path);
            }

            // 4. Delete Fee Accounts & Enrollments
            foreach ($student->enrollments as $enrollment) {
                if ($enrollment->feeAccount) {
                    $enrollment->feeAccount->delete();
                }
                $enrollment->delete();
            }

            // 5. Create Audit Log
            $this->logAction(
                $userId,
                'ADMISSION_DELETED',
                'students',
                $student->student_id,
                json_encode($student->toArray()),
                "Deleted student record: {$student->student_name} (Adm: {$student->admission_no})"
            );

            // 6. Delete Admission & Student
            $admission->delete();
            $student->delete();
        });
    }

    /**
     * Generate unique Admission Number (ADM001, ADM002, ...).
     */
    public function generateAdmissionNumber(): string
    {
        $lastStudent = Student::where('admission_no', 'LIKE', 'ADM%')
            ->lockForUpdate()
            ->orderBy('admission_no', 'desc')
            ->first();

        if (!$lastStudent) {
            return 'ADM001';
        }

        $lastNumber = (int) substr($lastStudent->admission_no, 3);
        $newNumber = $lastNumber + 1;

        return 'ADM' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Log action to audit_logs.
     */
    protected function logAction(int $userId, string $action, string $tableName, $recordId, $oldValue, $newValue): void
    {
        AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'table_name' => $tableName,
            'record_id' => $recordId,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'ip_address' => request()->ip(),
        ]);
    }
}
