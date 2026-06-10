<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\EnrollmentService;
use Illuminate\Http\Request;

class StudentEnrollmentController extends Controller
{
    public function index(Student $student)
    {
        $enrollments = $student->enrollments()
            ->with(['academicYear', 'classRoom', 'section'])
            ->latest('created_at')
            ->get();

        $academicYears = AcademicYear::query()
            ->orderByDesc('start_date')
            ->get();

        $classes = ClassRoom::query()
            ->orderBy('display_order')
            ->get();

        $sections = Section::query()
            ->orderBy('class_id')
            ->orderBy('section_name')
            ->get();

        return view('students.enrollments', compact(
            'student',
            'enrollments',
            'academicYears',
            'classes',
            'sections'
        ));
    }

    public function store(Request $request, Student $student)
    {
        $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,academic_year_id'],
            'class_id' => ['required', 'exists:classes,class_id'],
            'section_id' => ['required', 'exists:sections,section_id'],
            'promotion_status' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'string', 'max:20'],
        ]);

        try {
            // Use the new EnrollmentService to create enrollment with associated fee accounts
            $enrollmentService = new EnrollmentService();
            $enrollmentService->createEnrollmentWithFees(
                $student->student_id,
                $request->academic_year_id,
                $request->class_id,
                $request->section_id,
                $request->promotion_status,
                $request->status,
                auth()->id() // Assuming the current authenticated user is performing the action
            );

            return redirect("/students/{$student->student_id}/enrollments")
                ->with('success', 'Enrollment created successfully with fee accounts.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error creating enrollment: ' . $e->getMessage());
        }
    }
}
