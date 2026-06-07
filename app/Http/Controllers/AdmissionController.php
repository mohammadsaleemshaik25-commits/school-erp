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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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
            'pending_verification' => Admission::whereIn('admission_status', ['SUBMITTED', 'DRAFT'])->count(),
            'approved_admissions' => Admission::where('admission_status', 'APPROVED')->count(),
            'rejected_admissions' => Admission::where('admission_status', 'REJECTED')->count(),
            'transfer_admissions' => Admission::whereNotNull('transferred_from_admission_id')->count(),
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
        $isDraft = $request->input('save_as_draft') === 'true';

        // Draft saves use lenient validation; full submissions use strict validation
        if ($isDraft) {
            $validationRules = [
                'student_name'    => 'required|string|max:100',
                'dob'             => 'nullable|date',
                'gender'          => 'nullable|string|max:10',
                'nationality'     => 'nullable|string|max:50',
                'father_name'     => 'nullable|string|max:100',
                'mother_name'     => 'nullable|string|max:100',
                'guardian_name'   => 'nullable|string|max:100',
                'aadhaar_no'      => 'nullable|string|max:20|unique:students,aadhaar_no',
                'pen_no'          => 'nullable|string|max:30|unique:students,pen_no',
                'phone_primary'   => 'nullable|string|max:15',
                'phone_secondary' => 'nullable|string|max:15',
                'email'           => 'nullable|email|max:100',
                'address'         => 'nullable|string',
                'permanent_address' => 'nullable|string',
                'village'         => 'nullable|string|max:100',
                'district'        => 'nullable|string|max:100',
                'state'           => 'nullable|string|max:100',
                'pin_code'        => 'nullable|string|max:10',
                'religion'        => 'nullable|string|max:50',
                'category'        => 'nullable|string|max:50',
                'blood_group'     => 'nullable|string|max:10',
                'occupation'      => 'nullable|string|max:100',
                'annual_income'   => 'nullable|numeric',
                'previous_school' => 'nullable|string|max:200',
                'previous_class'  => 'nullable|string|max:50',
                'admission_date'  => 'nullable|date',
                // DB schema enforces NOT NULL on these FK columns even in draft
                'academic_year_id'=> 'required|exists:academic_years,academic_year_id',
                'class_id'        => 'required|exists:classes,class_id',
                'section_id'      => 'required|exists:sections,section_id',
                'photo'           => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'documents.*'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            ];
        } else {
            $validationRules = [
                'student_name'    => 'required|string|max:100',
                'dob'             => 'required|date',
                'gender'          => 'required|string|max:10',
                'nationality'     => 'nullable|string|max:50',
                'father_name'     => 'required|string|max:100',
                'mother_name'     => 'required|string|max:100',
                'guardian_name'   => 'nullable|string|max:100',
                'aadhaar_no'      => 'required|string|max:20|unique:students,aadhaar_no',
                'pen_no'          => 'required|string|max:30|unique:students,pen_no',
                'phone_primary'   => 'required|string|max:15',
                'phone_secondary' => 'nullable|string|max:15',
                'email'           => 'nullable|email|max:100',
                'address'         => 'required|string',
                'permanent_address' => 'nullable|string',
                'village'         => 'nullable|string|max:100',
                'district'        => 'nullable|string|max:100',
                'state'           => 'nullable|string|max:100',
                'pin_code'        => 'nullable|string|max:10',
                'religion'        => 'nullable|string|max:50',
                'category'        => 'nullable|string|max:50',
                'blood_group'     => 'nullable|string|max:10',
                'occupation'      => 'nullable|string|max:100',
                'annual_income'   => 'nullable|numeric',
                'previous_school' => 'nullable|string|max:200',
                'previous_class'  => 'nullable|string|max:50',
                'admission_date'  => 'required|date',
                'academic_year_id'=> 'required|exists:academic_years,academic_year_id',
                'class_id'        => 'required|exists:classes,class_id',
                'section_id'      => 'required|exists:sections,section_id',
                'photo'           => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'documents.*'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            ];
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            if ($isDraft || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Validation failed',
                    'message' => $validator->errors()->first(),
                    'errors'  => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $data = $request->all();

            // Handle cropped photo if provided
            if ($request->filled('cropped_photo_data')) {
                $croppedData = $request->input('cropped_photo_data');
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $croppedData));
                $fileName = 'photo_' . time() . '.jpg';
                $filePath = 'students/photos/' . $fileName;
                Storage::disk('public')->put($filePath, $imageData);
                $data['cropped_photo_path'] = $filePath;
                unset($data['photo']); // Don't use original file
            }

            // Set admission status based on draft flag
            $data['admission_status'] = $isDraft ? Admission::STATUS_DRAFT : Admission::STATUS_SUBMITTED;

            // For drafts, fill in default values for any missing required DB fields
            if ($isDraft) {
                $data['dob']           = $data['dob'] ?? now()->subYears(10)->format('Y-m-d');
                $data['gender']        = $data['gender'] ?? 'MALE';
                $data['father_name']   = $data['father_name'] ?? 'N/A';
                $data['mother_name']   = $data['mother_name'] ?? 'N/A';
                $data['aadhaar_no']    = $data['aadhaar_no'] ?? 'DRAFT_' . time();
                $data['pen_no']        = $data['pen_no'] ?? 'DPEN_' . time();
                $data['phone_primary'] = $data['phone_primary'] ?? '0000000000';
                $data['address']       = $data['address'] ?? 'Draft address';
                $data['admission_date']= $data['admission_date'] ?? now()->format('Y-m-d');
            }

            $admission = $this->admissionService->createAdmission($data, Auth::id());

            // Always return JSON for draft saves
            if ($isDraft || $request->expectsJson()) {
                return response()->json([
                    'success'      => true,
                    'admission_id' => $admission->admission_id,
                    'admission_no' => $admission->student->admission_no,
                    'status'       => $admission->admission_status
                ]);
            }

            return redirect()->route('admissions.show', $admission->admission_id)
                ->with('success', 'Admission submitted successfully! Admission No: ' . $admission->student->admission_no);
        } catch (Exception $e) {
            if ($isDraft || $request->expectsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
            }
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

            // Log document upload
            \App\Models\AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'DOCUMENT_UPLOADED',
                'table_name' => 'student_documents',
                'record_id' => $student->student_id,
                'old_value' => $existing ? "Replaced existing document of type: {$type}" : null,
                'new_value' => "Document type: {$type} uploaded for student: {$student->student_name}",
                'ip_address' => $request->ip(),
            ]);

            return redirect()->back()->with('success', "Document uploaded successfully.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Verify admission documents (Management Action)
     */
    public function verify(Admission $admission)
    {
        try {
            $this->admissionService->verifyAdmission($admission->admission_id, auth()->id());
            return redirect()->back()->with('success', "Admission documents for {$admission->student->student_name} have been verified.");
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
     * Admit student (Finalize admission)
     */
    public function admit(Admission $admission)
    {
        try {
            $this->admissionService->admitStudent($admission->admission_id, auth()->id());
            return redirect()->back()->with('success', "Student {$admission->student->student_name} has been admitted successfully.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reject admission (Management Action)
     */
    public function reject(Request $request, Admission $admission)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        try {
            $this->admissionService->rejectAdmission($admission->admission_id, auth()->id(), $request->rejection_reason);
            return redirect()->back()->with('success', "Admission for {$admission->student->student_name} has been rejected.");
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
     * Verify a specific document (Management Action)
     */
    public function verifyDocument(Request $request, $documentId)
    {
        $role = strtoupper(optional(auth()->user()->role)->role_name ?? '');
        if (!in_array($role, ['ADMIN', 'ADMINISTRATOR', 'PRINCIPAL', 'CORRESPONDENT'])) {
            return response()->json(['success' => false, 'error' => 'Unauthorized action.'], 403);
        }

        try {
            $document = \App\Models\StudentDocument::findOrFail($documentId);
            $oldStatus = $document->verification_status;
            
            $document->update([
                'verification_status' => \App\Models\StudentDocument::STATUS_VERIFIED,
                'verified_at' => now(),
                'verified_by' => Auth::id(),
            ]);

            // Log the action
            \App\Models\AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'DOCUMENT_VERIFIED',
                'table_name' => 'student_documents',
                'record_id' => $documentId,
                'old_value' => $oldStatus,
                'new_value' => \App\Models\StudentDocument::STATUS_VERIFIED,
                'ip_address' => $request->ip(),
            ]);

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Reject a specific document (Management Action)
     */
    public function rejectDocument(Request $request, $documentId)
    {
        $role = strtoupper(optional(auth()->user()->role)->role_name ?? '');
        if (!in_array($role, ['ADMIN', 'ADMINISTRATOR', 'PRINCIPAL', 'CORRESPONDENT'])) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => 'Unauthorized action.'], 403);
            }
            return back()->with('error', 'Unauthorized action.');
        }

        $request->validate([
            'remarks' => 'required|string|max:500',
        ]);

        try {
            $document = \App\Models\StudentDocument::findOrFail($documentId);
            $oldStatus = $document->verification_status;

            $document->update([
                'verification_status' => \App\Models\StudentDocument::STATUS_REJECTED,
                'verified_at' => now(),
                'verified_by' => Auth::id(),
                'remarks' => $request->remarks,
            ]);

            // Log the action
            \App\Models\AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'DOCUMENT_REJECTED',
                'table_name' => 'student_documents',
                'record_id' => $documentId,
                'old_value' => $oldStatus,
                'new_value' => \App\Models\StudentDocument::STATUS_REJECTED,
                'ip_address' => $request->ip(),
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => true]);
            }

            return back()->with('success', 'Document rejected successfully.');
        } catch (Exception $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display the Verification Queue of documents awaiting review (Management Action)
     */
    public function verificationQueue(Request $request)
    {
        $role = strtoupper(optional(auth()->user()->role)->role_name ?? '');
        if (!in_array($role, ['ADMIN', 'ADMINISTRATOR', 'PRINCIPAL', 'CORRESPONDENT'])) {
            abort(403, 'Unauthorized action.');
        }

        $search = $request->get('search');
        $docType = $request->get('document_type');

        $query = \App\Models\StudentDocument::with(['student.enrollments' => function($q) {
            $q->where('status', 'ACTIVE')->with(['classRoom', 'section']);
        }])
        ->where('verification_status', \App\Models\StudentDocument::STATUS_UPLOADED);

        if ($search) {
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('student_name', 'like', "%{$search}%")
                  ->orWhere('admission_no', 'like', "%{$search}%");
            });
        }

        if ($docType) {
            $query->where('document_type', $docType);
        }

        $documents = $query->orderBy('uploaded_at', 'desc')->paginate(20);

        $documentTypes = [
            'PHOTO' => 'Student Photo',
            'STUDENT_AADHAAR' => 'Student Aadhaar',
            'BIRTH_CERTIFICATE' => 'Birth Certificate',
            'TC' => 'Transfer Certificate',
            'PARENT_AADHAAR' => 'Parent Aadhaar',
        ];

        return view('admissions.verification_queue', compact('documents', 'documentTypes', 'search', 'docType'));
    }

    /**
     * Reveal sensitive data (Aadhaar, PEN) with audit logging
     */
    public function revealSensitiveData(Request $request, Admission $admission)
    {
        $role = strtoupper(optional(auth()->user()->role)->role_name ?? '');

        if (!in_array($role, ['ADMIN', 'ADMINISTRATOR', 'PRINCIPAL', 'CORRESPONDENT'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $field = $request->get('field'); // 'aadhaar' or 'pen'
        $student = $admission->student;

        $value = match($field) {
            'aadhaar' => $student->aadhaar_no,
            'pen' => $student->pen_no,
            default => null,
        };

        if (!$value) {
            return response()->json(['error' => 'Field not found'], 404);
        }

        // Log the reveal action
        \App\Models\AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'SENSITIVE_DATA_REVEALED',
            'table_name' => 'students',
            'record_id' => $student->student_id,
            'old_value' => null,
            'new_value' => "Revealed {$field} for student: {$student->student_name}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'field' => $field,
            'value' => $value,
            'student_name' => $student->student_name,
        ]);
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
