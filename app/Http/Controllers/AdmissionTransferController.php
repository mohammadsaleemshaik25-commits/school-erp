<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Admission;
use App\Models\Student;
use App\Models\ClassRoom;
use App\Models\Section;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;
use Exception;

class AdmissionTransferController extends Controller
{
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
        ]);

        $student = Student::findOrFail($request->student_id);
        $oldAdmission = Admission::where('student_id', $student->student_id)
            ->orderByDesc('created_at')
            ->first();

        DB::beginTransaction();
        try {
            // 1. Generate New Admission No
            $newAdmissionNo = 'ADM-T-' . date('Y') . str_pad(Student::count() + 1, 4, '0', STR_PAD_LEFT);
            
            // Update student with new admission no (keeping history in admissions table)
            $student->update(['admission_no' => $newAdmissionNo]);

            // 2. Create New Admission Record
            $newAdmission = Admission::create([
                'student_id' => $student->student_id,
                'transferred_from_admission_id' => $oldAdmission ? $oldAdmission->admission_id : null,
                'academic_year_id' => $request->academic_year_id,
                'class_id' => $request->class_id,
                'section_id' => $request->section_id,
                'admission_status' => 'SUBMITTED',
                'created_by' => auth()->id(),
            ]);

            DB::commit();
            return redirect()->route('admissions.show', $newAdmission->admission_id)
                ->with('success', "Student transferred successfully. New Admission No: {$newAdmissionNo}");
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', "Transfer failed: " . $e->getMessage());
        }
    }
}
