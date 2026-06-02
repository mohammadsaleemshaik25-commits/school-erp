<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = $request->search;

        $students = Student::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('student_name', 'like', "%{$search}%")
                        ->orWhere('admission_no', 'like', "%{$search}%");
                });
            })
            ->orderBy('student_name')
            ->get();

        return response()->json($students);
    }

    public function show(Student $student): JsonResponse
    {
        return response()->json([
            'student' => $student,
            'current_enrollment' => $student->currentEnrollment(),
        ]);
    }

    public function enrollments(Student $student): JsonResponse
    {
        $enrollments = $student->enrollments()
            ->with(['academicYear', 'classRoom', 'section'])
            ->join('academic_years', 'student_enrollments.academic_year_id', '=', 'academic_years.academic_year_id')
            ->orderBy('academic_years.start_date')
            ->select('student_enrollments.*')
            ->get();

        return response()->json($enrollments);
    }

    public function enrollmentIndex(Request $request): JsonResponse
    {
        $enrollments = StudentEnrollment::query()
            ->with(['student', 'academicYear', 'classRoom', 'section'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when(! $request->has('status'), fn ($q) => $q->where('status', 'ACTIVE'))
            ->when($request->academic_year_id, fn ($q, $id) => $q->where('academic_year_id', $id))
            ->get();

        return response()->json($enrollments);
    }
}
