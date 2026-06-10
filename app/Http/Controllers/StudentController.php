<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentFeeAccount;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Section;
use App\Services\EnrollmentService;
use App\Models\FeeStructure;
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
        $academicYears = AcademicYear::where('is_active', true)->orderBy('start_date', 'desc')->get();
        $classes = ClassRoom::orderBy('display_order')->get();
        $sections = Section::with('classRoom')->get();

        return view('students.create', compact('admissionNo', 'academicYears', 'classes', 'sections'));
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
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'academic_year_id' => ['required', 'exists:academic_years,academic_year_id'],
            'class_id' => ['required', 'exists:classes,class_id'],
            'section_id' => ['required', 'exists:sections,section_id'],
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $fileName = 'student_' . time() . '.' . $file->getClientOriginalExtension();
            $photoPath = $file->storeAs('students/photos', $fileName, 'public');
        }

        $validated['photo_path'] = $photoPath;
        unset($validated['photo']);

        // Extract enrollment data for the new service
        $academicYearId = $validated['academic_year_id'];
        $classId = $validated['class_id'];
        $sectionId = $validated['section_id'];
        unset($validated['academic_year_id'], $validated['class_id'], $validated['section_id']);

        try {
            // Create student
            $student = Student::create($validated);

            // Use the new EnrollmentService to create enrollment with associated fee accounts
            $enrollmentService = new EnrollmentService();
            $enrollmentService->createEnrollmentWithFees(
                $student->student_id,
                $academicYearId,
                $classId,
                $sectionId,
                'NEW', // Assuming this is a new admission, not a promotion
                'ACTIVE',
                auth()->id() // Assuming the current authenticated user is performing the action
            );

            return redirect('/students')->with('success', 'Student added successfully with enrollment and fee account created.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error adding student: ' . $e->getMessage());
        }
    }

    public function edit(Student $student)
    {
        return view('students.edit', compact('student'));
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'admission_no' => ['required', 'string', 'max:30', 'unique:students,admission_no,' . $student->student_id . ',student_id'],
            'pen_no' => ['required', 'string', 'max:30', 'unique:students,pen_no,' . $student->student_id . ',student_id'],
            'aadhaar_no' => ['required', 'string', 'max:20', 'unique:students,aadhaar_no,' . $student->student_id . ',student_id'],
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
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $fileName = 'student_' . $student->student_id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $photoPath = $file->storeAs('students/photos', $fileName, 'public');
            $validated['photo_path'] = $photoPath;
        }

        unset($validated['photo']);

        $student->update($validated);

        return redirect("/students/{$student->student_id}");
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

    public function history(Student $student)
    {
        $enrollmentHistory = $student->enrollments()
            ->with(['academicYear', 'classRoom', 'section'])
            ->join('academic_years', 'student_enrollments.academic_year_id', '=', 'academic_years.academic_year_id')
            ->orderBy('academic_years.start_date')
            ->select('student_enrollments.*')
            ->get();

        return view('students.history', compact('student', 'enrollmentHistory'));
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
