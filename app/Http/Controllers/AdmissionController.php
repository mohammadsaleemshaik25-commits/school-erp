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
     * AJAX Student Finder for Admissions
     */
    public function finder(Request $request)
    {
        $q = trim($request->get('q', ''));
        $classId = $request->get('class_id');
        $status = $request->get('status');

        $query = Admission::with(['student', 'classRoom', 'section', 'academicYear']);

        if (!empty($q)) {
            $query->whereHas('student', function ($sub) use ($q) {
                $sub->where('student_name', 'like', "%{$q}%")
                    ->orWhere('admission_no', 'like', "{$q}%")
                    ->orWhere('father_name', 'like', "%{$q}%")
                    ->orWhere('mother_name', 'like', "%{$q}%")
                    ->orWhere('guardian_name', 'like', "%{$q}%");
            });
        }

        if ($classId) {
            $query->where('class_id', $classId);
        }

        if ($status) {
            $query->where('admission_status', $status);
        }

        $results = $query->orderBy('created_at', 'desc')->limit(50)->get();

        return response()->json(
            $results->map(function ($adm) {
                $student = $adm->student;
                return [
                    'admission_id' => $adm->admission_id,
                    'admission_no' => $student->admission_no,
                    'student_name' => strtoupper($student->student_name),
                    'class_name' => $adm->classRoom->class_name,
                    'section_name' => $adm->section->section_name ?? 'N/A',
                    'father_name' => $student->father_name,
                    'phone_primary' => $student->phone_primary,
                    'photo_url' => $student->photo_path ? asset('storage/' . $student->photo_path) : null,
                    'admission_date' => $adm->created_at->format('d M Y'),
                    'status' => $adm->admission_status,
                ];
            })
        );
    }

    /**
     * Dashboard Stats for Admissions
     */
    public function dashboardStats()
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();
        
        $stats = [
            'total_students' => \App\Models\Student::where('status', 'ACTIVE')->count(),
            'today_admissions' => Admission::whereDate('created_at', $today)->count(),
            'month_admissions' => Admission::where('created_at', '>=', $thisMonth)->count(),
            'pending_verification' => Admission::whereIn('admission_status', ['SUBMITTED', 'DRAFT'])->count(),
            'approved_admissions' => Admission::where('admission_status', 'APPROVED')->count(),
            'rejected_admissions' => Admission::where('admission_status', 'REJECTED')->count(),
            'missing_docs' => Admission::whereDoesntHave('student.documents')->count(),
            'missing_photos' => \App\Models\Student::whereNull('photo_path')->orWhere('photo_path', '')->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $classes = ClassRoom::all();
        $academicYears = AcademicYear::all();
        
        // Initial page load with pagination
        $admissions = Admission::with(['student', 'academicYear', 'classRoom', 'section'])
            ->orderByDesc('created_at')
            ->paginate(20);

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
     * Store a newly uploaded document for a student.
     */
    public function storeDocument(Request $request, Admission $admission)
    {
        $request->validate([
            'document_type' => 'required|string',
            'document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        try {
            $student = $admission->student;
            $file = $request->file('document');
            $type = $request->document_type;

            // Delete existing document of the same type
            $existing = \App\Models\StudentDocument::where('student_id', $student->student_id)
                ->where('document_type', $type)
                ->first();

            if ($existing) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($existing->file_path);
                $existing->delete();
            }

            $path = $file->store('students/documents', 'public');

            \App\Models\StudentDocument::create([
                'student_id' => $student->student_id,
                'document_type' => $type,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'uploaded_at' => now(),
            ]);

            return redirect()->back()->with('success', "Document uploaded successfully.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Approve admission (Management Action)
     */
    public function approve(Admission $admission)
    {
        try {
            $this->admissionService->approveAdmission($admission->admission_id, auth()->id());
            return redirect()->back()->with('success', "Admission for {$admission->student->student_name} has been approved.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
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
