<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Section;
use App\Models\StudentEnrollment;
use App\Models\AuditLog;
use App\Services\PromotionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class PromotionController extends Controller
{
    protected PromotionService $promotionService;

    public function __construct(PromotionService $promotionService)
    {
        $this->promotionService = $promotionService;
    }

    /**
     * Display the promotion selection form
     */
    public function index()
    {
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $classes = ClassRoom::orderBy('display_order')->get();
        $sections = Section::all();

        return view('promotions.index', compact('academicYears', 'classes', 'sections'));
    }

    /**
     * List students for promotion based on source selection
     */
    public function listStudents(Request $request)
    {
        $request->validate([
            'source_academic_year_id' => 'required|exists:academic_years,academic_year_id',
            'source_class_id' => 'required|exists:classes,class_id',
            'source_section_id' => 'nullable|exists:sections,section_id',
        ]);

        $query = StudentEnrollment::with('student')
            ->where('academic_year_id', $request->source_academic_year_id)
            ->where('class_id', $request->source_class_id)
            ->where('status', 'ACTIVE');

        if ($request->filled('source_section_id')) {
            $query->where('section_id', $request->source_section_id);
        }

        $enrollments = $query->get();

        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $classes = ClassRoom::orderBy('display_order')->get();
        $sections = Section::all();

        return view('promotions.process', compact('enrollments', 'academicYears', 'classes', 'sections', 'request'));
    }

    /**
     * Process promotion for single or multiple students
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,student_id',
            'status' => 'required|in:PROMOTED,DETAINED,TRANSFERRED,DROPPED',
            'target_academic_year_id' => 'required_if:status,PROMOTED,DETAINED|exists:academic_years,academic_year_id',
            'target_class_id' => 'required_if:status,PROMOTED,DETAINED|exists:classes,class_id',
            'target_section_id' => 'required_if:status,PROMOTED,DETAINED|exists:sections,section_id',
        ]);

        $commonData = $request->only(['status', 'target_academic_year_id', 'target_class_id', 'target_section_id']);
        
        try {
            $results = $this->promotionService->bulkPromote($request->student_ids, $commonData, Auth::id());

            $msg = "Promotion completed: {$results['success']} successful, {$results['failed']} failed.";
            if ($results['failed'] > 0) {
                return redirect()->route('promotions.index')->with('warning', $msg)->with('errors_list', $results['errors']);
            }

            return redirect()->route('promotions.index')->with('success', $msg);
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Promotion History Report
     */
    public function report(Request $request)
    {
        $query = AuditLog::with('user')
            ->where('action', 'STUDENT_PROMOTED')
            ->orderBy('created_at', 'desc');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('promotions.report', compact('logs'));
    }
}
