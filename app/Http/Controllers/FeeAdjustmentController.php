<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeeAdjustmentRequest;
use App\Http\Requests\DecideFeeAdjustmentRequest;
use App\Services\FinanceService;
use App\Models\StudentFeeAdjustment;
use Illuminate\Http\Request;
use Exception;

use App\Models\AcademicYear;
use App\Models\StudentFeeAccount;
use App\Models\ClassRoom;
use App\Models\Section;
use Carbon\Carbon;

class FeeAdjustmentController extends Controller
{
    protected FinanceService $financeService;

    public function __construct(FinanceService $financeService)
    {
        $this->financeService = $financeService;
    }

    /**
     * Unified Concession Management Index
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $roleName = strtoupper($user->role->role_name ?? '');
        $isManagement = in_array($roleName, ['ADMIN', 'ADMINISTRATOR', 'PRINCIPAL', 'CORRESPONDENT']);
        $isClerk = $roleName === 'CLERK';

        $classes = ClassRoom::all();
        $sections = Section::all();
        $academicYears = AcademicYear::all();

        // Dashboard Stats (Only for management)
        $stats = [
            'total_requested' => 0,
            'total_approved' => 0,
            'total_rejected' => 0,
            'total_approved_amount' => 0,
        ];

        if ($isManagement) {
            $stats = [
                'total_requested' => StudentFeeAdjustment::count(),
                'total_approved' => StudentFeeAdjustment::where('approval_status', 'APPROVED')->count(),
                'total_rejected' => StudentFeeAdjustment::where('approval_status', 'REJECTED')->count(),
                'total_approved_amount' => StudentFeeAdjustment::where('approval_status', 'APPROVED')->sum('discount_amount'),
            ];
        }

        // History & Report Query
        $query = StudentFeeAdjustment::with([
            'feeAccount.enrollment.student', 
            'feeAccount.enrollment.classRoom', 
            'requester', 
            'approver'
        ])->orderBy('created_at', 'desc');

        // Role-based filtering: Clerks only see their own requests
        if ($isClerk) {
            $query->where('requested_by', $user->user_id);
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('approval_status', $request->status);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->whereHas('feeAccount.enrollment.student', function ($sub) use ($q) {
                $sub->where('student_name', 'like', "%{$q}%")
                    ->orWhere('admission_no', 'like', "%{$q}%");
            });
        }

        if ($request->filled('class_id')) {
            $query->whereHas('feeAccount.enrollment', function($q) use ($request) {
                $q->where('class_id', $request->class_id);
            });
        }

        if ($request->filled('section_id')) {
            $query->whereHas('feeAccount.enrollment', function($q) use ($request) {
                $q->where('section_id', $request->section_id);
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $adjustments = $query->paginate(15)->withQueryString();

        return view('fees.adjustments.index', compact('adjustments', 'stats', 'classes', 'sections', 'academicYears'));
    }

    /**
     * AJAX Student Finder for Concessions
     */
    public function finder(Request $request)
    {
        $q = trim($request->get('q', ''));
        $classId = $request->get('class_id');
        $sectionId = $request->get('section_id');

        if (empty($q) && empty($classId) && empty($sectionId)) {
            return response()->json([]);
        }

        $activeYear = AcademicYear::where('is_active', true)->first();
        if (!$activeYear) return response()->json([]);

        $query = StudentFeeAccount::with([
                'enrollment.student', 
                'enrollment.classRoom', 
                'enrollment.section',
                'adjustments' // To show current status
            ])
            ->whereHas('enrollment', function($query) use ($activeYear, $classId, $sectionId) {
                $query->where('academic_year_id', $activeYear->academic_year_id);
                if ($classId) $query->where('class_id', $classId);
                if ($sectionId) $query->where('section_id', $sectionId);
            });

        if (!empty($q)) {
            $query->whereHas('enrollment.student', function ($query) use ($q) {
                $query->where(function($inner) use ($q) {
                    $inner->where('student_name', 'like', "%{$q}%")
                          ->orWhere('admission_no', 'like', "{$q}%")
                          ->orWhere('father_name', 'like', "%{$q}%")
                          ->orWhere('mother_name', 'like', "%{$q}%")
                          ->orWhere('guardian_name', 'like', "%{$q}%");
                });
            });
        }

        $results = $query->limit(20)->get();

        return response()->json(
            $results->map(function ($acc) {
                $student = $acc->enrollment->student;
                $latestAdjustment = $acc->adjustments->sortByDesc('created_at')->first();
                
                return [
                    'account_id' => $acc->account_id,
                    'student_name' => strtoupper($student->student_name),
                    'admission_no' => $student->admission_no,
                    'class_name' => $acc->enrollment->classRoom->class_name,
                    'section_name' => $acc->enrollment->section->section_name ?? 'N/A',
                    'father_name' => $student->father_name,
                    'phone_primary' => $student->phone_primary,
                    'photo_url' => $student->photo_path ? asset('storage/' . $student->photo_path) : null,
                    'tuition_fee' => (float)$acc->final_tuition_fee,
                    'concession_amount' => (float)$acc->waived_amount,
                    'outstanding_amount' => (float)$acc->remaining_balance,
                    'current_status' => $latestAdjustment ? $latestAdjustment->approval_status : 'NONE',
                    'adjustment_id' => $latestAdjustment ? $latestAdjustment->adjustment_id : null,
                ];
            })
        );
    }

    /**
     * Create concession / waiver request
     */
    public function store(StoreFeeAdjustmentRequest $request)
    {
        try {
            $data = $request->validated();
            
            // If discount percentage is provided but amount is not, calculate the amount
            if (empty($data['discount_amount']) && !empty($data['discount_percent'])) {
                $account = \App\Models\StudentFeeAccount::findOrFail($data['account_id']);
                $baseFee = (float) $account->final_tuition_fee;
                $data['discount_amount'] = round(($baseFee * (float)$data['discount_percent']) / 100, 2);
            }

            $adjustment = $this->financeService->requestAdjustment($data, auth()->id());

            $roleName = strtoupper(auth()->user()->role->role_name ?? '');
            if ($roleName === 'CLERK') {
                return redirect()
                    ->back()
                    ->with('success_clerk', [
                        'id' => $adjustment->adjustment_id,
                        'message' => 'Concession Request Submitted',
                        'status' => 'Pending Approval',
                        'detail' => 'This request has been forwarded to the Principal / Correspondent for review.'
                    ]);
            }

            return redirect()
                ->route('fees.adjustments.index')
                ->with('success', 'Concession request logged successfully. Awaiting administrative review.');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Authorize decision on concession (Principal/Correspondent only)
     */
    public function decide(DecideFeeAdjustmentRequest $request, int $adjustmentId)
    {
        try {
            $adjustment = $this->financeService->decideAdjustment(
                $adjustmentId,
                $request->validated()['status'],
                $request->validated()['decision_remarks'] ?? null,
                auth()->id()
            );

            return redirect()
                ->back()
                ->with('success', 'Concession request #' . $adjustmentId . ' has been ' . strtolower($adjustment->approval_status) . '.');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}