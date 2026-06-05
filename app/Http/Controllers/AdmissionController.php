<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Section;
use App\Models\StudentDocument;
use App\Services\AdmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class AdmissionController extends Controller
{
    protected AdmissionService $admissionService;

    public function __construct(AdmissionService $admissionService)
    {
        $this->admissionService = $admissionService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Admission::with(['student', 'academicYear', 'classRoom', 'section']);

        // Enhanced Search Filters
        if ($request->filled('admission_no')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('admission_no', 'like', "%{$request->admission_no}%");
            });
        }

        if ($request->filled('student_name')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('student_name', 'like', "%{$request->student_name}%");
            });
        }

        if ($request->filled('father_name')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('father_name', 'like', "%{$request->father_name}%");
            });
        }

        if ($request->filled('phone_primary')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('phone_primary', 'like', "%{$request->phone_primary}%");
            });
        }

        if ($request->filled('aadhaar_no')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('aadhaar_no', 'like', "%{$request->aadhaar_no}%");
            });
        }

        if ($request->filled('pen_no')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('pen_no', 'like', "%{$request->pen_no}%");
            });
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('admission_status')) {
            $query->where('admission_status', $request->admission_status);
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        $admissions = $query->orderByDesc('created_at')->paginate(15)->withQueryString();
        $classes = ClassRoom::all();
        $academicYears = AcademicYear::all();

        return view('admissions.index', compact('admissions', 'classes', 'academicYears'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $academicYears = AcademicYear::where('is_active', true)->get();
        if ($academicYears->isEmpty()) {
            $academicYears = AcademicYear::all();
        }
        $classes = ClassRoom::orderBy('display_order')->get();
        $sections = Section::all();

        return view('admissions.create', compact('academicYears', 'classes', 'sections'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_name' => 'required|string|max:100',
            'dob' => 'required|date',
            'gender' => 'required|string|max:10',
            'father_name' => 'required|string|max:100',
            'mother_name' => 'required|string|max:100',
            'aadhaar_no' => 'required|string|max:20|unique:students,aadhaar_no',
            'pen_no' => 'required|string|max:30|unique:students,pen_no',
            'phone_primary' => 'required|string|max:15',
            'address' => 'required|string',
            'admission_date' => 'required|date',
            'academic_year_id' => 'required|exists:academic_years,academic_year_id',
            'class_id' => 'required|exists:classes,class_id',
            'section_id' => 'required|exists:sections,section_id',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'documents.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        try {
            $admission = $this->admissionService->createAdmission($request->all(), Auth::id());
            return redirect()->route('admissions.show', $admission->admission_id)
                ->with('success', 'Admission created successfully!');
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Admission $admission)
    {
        $admission->load(['student.documents', 'academicYear', 'classRoom', 'section', 'creator', 'approver']);
        $feeAccount = $admission->student->currentEnrollment()->feeAccount ?? null;

        return view('admissions.show', compact('admission', 'feeAccount'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Admission $admission)
    {
        $admission->load(['student.documents', 'academicYear', 'classRoom', 'section']);
        return view('admissions.edit', compact('admission'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Admission $admission)
    {
        $request->validate([
            'student_name' => 'required|string|max:100',
            'dob' => 'required|date',
            'father_name' => 'required|string|max:100',
            'mother_name' => 'required|string|max:100',
            'guardian_name' => 'nullable|string|max:100',
            'phone_primary' => 'required|string|max:15',
            'phone_secondary' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:100',
            'address' => 'required|string',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'documents.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        try {
            $this->admissionService->updateAdmission($admission, $request->all(), Auth::id());
            return redirect()->route('admissions.show', $admission->admission_id)
                ->with('success', 'Admission updated successfully!');
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete a specific student document
     */
    public function deleteDocument(Request $request, $documentId)
    {
        try {
            $this->admissionService->deleteDocument($documentId, Auth::id());
            return back()->with('success', 'Document deleted successfully.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Admission $admission)
    {
        $role = strtoupper(optional(auth()->user()->role)->role_name ?? '');
        if ($role !== 'ADMIN' && $role !== 'ADMINISTRATOR') {
            return back()->with('error', 'Only Administrators can delete student records.');
        }

        try {
            $this->admissionService->deleteAdmission($admission->admission_id, Auth::id());
            return redirect()->route('admissions.index')->with('success', 'Student record and all related data deleted successfully.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
