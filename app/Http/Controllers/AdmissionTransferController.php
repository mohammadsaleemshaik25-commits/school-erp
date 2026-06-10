<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Admission;
use App\Models\Student;
use App\Models\ClassRoom;
use App\Models\Section;
use App\Models\AcademicYear;
use App\Models\StudentEnrollment;
use App\Models\AuditLog;
use App\Services\EnrollmentService;
use Illuminate\Support\Facades\DB;
use Exception;

class AdmissionTransferController extends Controller
{
    protected EnrollmentService $enrollmentService;

    public function __construct(EnrollmentService $enrollmentService)
    {
        $this->enrollmentService = $enrollmentService;
    }

    public function index()
    {
        $classes = ClassRoom::all();
        $academicYears = AcademicYear::all();
        return view('admissions.transfer.index', compact('classes', 'academicYears'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,student_id',
            'academic_year_id' => 'required|exists:academic_years,academic_year_id',
            'class_id' => 'required|exists:class_rooms,class_id',
            'section_id' => 'nullable|exists:sections,section_id',
            'transfer_reason' => 'nullable|string|max:500',
        ]);

        $student = Student::findOrFail($request->student_id);
        $oldAdmission = Admission::where('student_id', $student->student_id)
            ->orderByDesc('created_at')
            ->first();

        DB::beginTransaction();
        try {
            // 1. Generate New Admission No
            $newAdmissionNo = 'ADM-T-' . date('Y') . str_pad(Student::count() + 1, 4, '0', STR_PAD_LEFT);

            // 2. Create New Admission Record
            $newAdmission = Admission::create([
                'student_id' => $student->student_id,
                'transferred_from_admission_id' => $oldAdmission ? $oldAdmission->admission_id : null,
                'academic_year_id' => $request->academic_year_id,
                'class_id' => $request->class_id,
                'section_id' => $request->section_id,
                'admission_status' => Admission::STATUS_SUBMITTED,
                'remarks' => $request->transfer_reason,
                'created_by' => auth()->id(),
            ]);

            // 3. Create New Enrollment with fees using EnrollmentService
            $this->enrollmentService->createEnrollmentWithFees(
                $student->student_id,
                $request->academic_year_id,
                $request->class_id,
                $request->section_id,
                'TRANSFER',
                'ACTIVE',
                auth()->id()
            );

            // 4. Log the transfer action
            $oldSectionName = $oldAdmission && $oldAdmission->section ? $oldAdmission->section->section_name : 'N/A';
            $oldValue = $oldAdmission ? "From: Class {$oldAdmission->classRoom->class_name}, Section {$oldSectionName}, AY {$oldAdmission->academicYear->year_name}" : 'New student';
            $newSectionName = $request->section_id ?? 'N/A';
            $newValue = "To: Class {$request->class_id}, Section {$newSectionName}, AY {$request->academic_year_id}. Reason: {$request->transfer_reason}";

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'STUDENT_TRANSFERRED',
                'table_name' => 'admissions',
                'record_id' => $newAdmission->admission_id,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'ip_address' => $request->ip(),
            ]);

            DB::commit();
            return redirect()->route('admissions.show', $newAdmission->admission_id)
                ->with('success', "Student transferred successfully. New Admission No: {$newAdmissionNo}");
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', "Transfer failed: " . $e->getMessage());
        }
    }

    /**
     * Show transfer timeline for a student
     */
    public function timeline($studentId)
    {
        $student = Student::findOrFail($studentId);
        $admissions = Admission::where('student_id', $studentId)
            ->with(['academicYear', 'classRoom', 'section', 'creator'])
            ->orderBy('created_at')
            ->get();

        $auditLogs = AuditLog::where('table_name', 'admissions')
            ->whereIn('record_id', $admissions->pluck('admission_id'))
            ->with('user')
            ->orderBy('created_at')
            ->get();

        return view('admissions.transfer.timeline', compact('student', 'admissions', 'auditLogs'));
    }
}
