<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $students = Student::query()
            ->when($search, function ($query, $search) {
                $query->where('student_name', 'like', "%{$search}%")
                    ->orWhere('admission_no', 'like', "%{$search}%");
            })
            ->orderBy('student_name')
            ->get();

        return view('students.index', compact('students', 'search'));
    }

    public function create()
    {
        $admissionNo = $this->generateAdmissionNumber();

        return view('students.create', compact('admissionNo'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'admission_no' => ['required', 'string', 'max:30', 'unique:students,admission_no'],
            'pen_no' => ['required', 'string', 'max:30', 'unique:students,pen_no'],
            'aadhaar_no' => ['required', 'string', 'max:20', 'unique:students,aadhaar_no'],
            'student_name' => ['required', 'string', 'max:100'],
            'dob' => ['required', 'date'],
            'gender' => ['required', 'string', 'max:10'],
            'father_name' => ['required', 'string', 'max:100'],
            'mother_name' => ['nullable', 'string', 'max:100'],
            'guardian_name' => ['nullable', 'string', 'max:100'],
            'phone_primary' => ['nullable', 'string', 'max:15'],
            'phone_secondary' => ['nullable', 'string', 'max:15'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string'],
            'admission_date' => ['required', 'date'],
            'status' => ['required', 'string', 'max:20'],
        ]);

        Student::create($validated);

        return redirect('/students');
    }

    public function show(Student $student)
    {
        $enrollmentHistory = $student->enrollments()
            ->with(['academicYear', 'classRoom', 'section'])
            ->join('academic_years', 'student_enrollments.academic_year_id', '=', 'academic_years.academic_year_id')
            ->orderBy('academic_years.start_date')
            ->select('student_enrollments.*')
            ->get();

        return view('students.show', compact('student', 'enrollmentHistory'));
    }

    public function idCard(Student $student)
    {
        $currentEnrollment = $student->currentEnrollment()
            ?? $student->latestEnrollment();
        $photoDocument = $student->photoDocument();

        return view('students.id-card', compact('student', 'currentEnrollment', 'photoDocument'));
    }

    private function generateAdmissionNumber()
    {
        $year = now()->format('Y');
        $prefix = "ADM{$year}";

        $lastAdmissionNo = Student::query()
            ->where('admission_no', 'like', "{$prefix}%")
            ->orderByDesc('admission_no')
            ->value('admission_no');

        $nextNumber = 1;

        if ($lastAdmissionNo) {
            $lastNumber = (int) substr($lastAdmissionNo, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        }

        return $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
}
