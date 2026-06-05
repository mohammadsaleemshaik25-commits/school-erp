<?php

namespace App\Http\Controllers;

use App\Models\StudentFeeAccount;
use App\Models\ClassRoom;
use App\Models\AcademicYear;
use App\Services\BooksDecisionService;
use Illuminate\Http\Request;
use Exception;
use InvalidArgumentException;

class BooksDecisionController extends Controller
{
    protected BooksDecisionService $booksService;

    public function __construct(BooksDecisionService $booksService)
    {
        $this->booksService = $booksService;
    }

    /**
     * Display a listing of students and their books purchase status.
     */
    public function index(Request $request)
    {
        $query = StudentFeeAccount::with(['enrollment.student', 'enrollment.classRoom', 'enrollment.section', 'enrollment.academicYear', 'decisionMaker']);

        // Search Filters
        if ($request->filled('q')) {
            $q = $request->q;
            $query->whereHas('enrollment.student', function ($sub) use ($q) {
                $sub->where('student_name', 'like', "%{$q}%")
                    ->orWhere('admission_no', 'like', "%{$q}%");
            });
        }

        if ($request->filled('books_status')) {
            $query->where('books_status', $request->books_status);
        }

        if ($request->filled('class_id')) {
            $query->whereHas('enrollment', function ($sub) use ($request) {
                $sub->where('class_id', $request->class_id);
            });
        }

        if ($request->filled('academic_year_id')) {
            $query->whereHas('enrollment', function ($sub) use ($request) {
                $sub->where('academic_year_id', $request->academic_year_id);
            });
        }

        $accounts = $query->orderBy('books_status', 'desc')->paginate(20)->withQueryString();
        $classes = ClassRoom::all();
        $academicYears = AcademicYear::all();

        return view('books.index', compact('accounts', 'classes', 'academicYears'));
    }

    /**
     * Show form to change books status.
     */
    public function edit(StudentFeeAccount $account)
    {
        $account->load(['enrollment.student', 'enrollment.classRoom', 'enrollment.section', 'enrollment.academicYear']);
        return view('books.change-status', compact('account'));
    }

    /**
     * Store the decision.
     */
    public function update(Request $request, StudentFeeAccount $account)
    {
        $request->validate([
            'books_status' => 'required|in:PENDING,SCHOOL,OUTSIDE',
            'confirm_student_name' => 'required|string',
        ]);

        $student = $account->enrollment->student;

        // Security Check: Name must match exactly
        if (strtoupper(trim($request->confirm_student_name)) !== strtoupper(trim($student->student_name))) {
            return back()->with('error', "The entered student name does not match. Please type '{$student->student_name}' exactly.");
        }

        try {
            \Illuminate\Support\Facades\Log::info('Books Decision Started', [
                'account_id' => $account->account_id,
                'status' => $request->books_status,
                'clerk_id' => auth()->id()
            ]);

            $this->booksService->updateDecision(
                $account->account_id,
                $request->books_status,
                auth()->id(),
                $request->ip()
            );

            \Illuminate\Support\Facades\Log::info('Books Decision Updated', [
                'account_id' => $account->account_id,
                'status' => $request->books_status
            ]);

            return redirect()->route('books.index')->with('success', "Books status updated successfully for {$student->student_name}.");
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Books Decision Failed', [
                'error' => $e->getMessage(),
                'account_id' => $account->account_id
            ]);
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reports for Books Purchase Decisions
     */
    public function report(Request $request)
    {
        $type = $request->get('type', 'PENDING');
        $query = StudentFeeAccount::with(['enrollment.student', 'enrollment.classRoom', 'enrollment.section', 'enrollment.academicYear'])
            ->where('books_status', $type);

        if ($request->filled('class_id')) {
            $query->whereHas('enrollment', function ($sub) use ($request) {
                $sub->where('class_id', $request->class_id);
            });
        }

        $accounts = $query->get();
        $classes = ClassRoom::all();
        
        $stats = [
            'total_revenue' => StudentFeeAccount::whereIn('books_status', ['SCHOOL', 'BOOKS_PAID'])->sum('books_fee_applied'),
            'total_collected' => \App\Models\Payment::where('status', 'SUCCESS')->sum('books_fee_paid'),
            'pending_count' => StudentFeeAccount::where('books_status', 'PENDING')->count(),
            'school_count' => StudentFeeAccount::where('books_status', 'SCHOOL')->count(),
            'outside_count' => StudentFeeAccount::where('books_status', 'OUTSIDE')->count(),
            'paid_count' => StudentFeeAccount::where('books_status', 'BOOKS_PAID')->count(),
        ];

        return view('books.report', compact('accounts', 'classes', 'type', 'stats'));
    }
}
