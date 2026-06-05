<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\StudentFeeAccount;
use App\Models\ClassRoom;
use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Clerk Daily Closing Report
     */
    public function clerkDailyClosing(Request $request): View
    {
        $role = strtoupper(optional(auth()->user()->role)->role_name ?? '');
        $dateStr = $request->get('date', Carbon::today()->toDateString());
        $clerkId = $request->get('clerk_id', auth()->id());

        // Clerks can only see their own report
        if ($role === 'CLERK') {
            $clerkId = auth()->id();
        }

        $query = Payment::whereDate('payment_date', $dateStr)
            ->where('collected_by', $clerkId);

        $payments = (clone $query)->where('status', 'SUCCESS')->get();
        $cancelledPayments = (clone $query)->where('status', 'CANCELLED')->get();

        $stats = [
            'successful_count' => $payments->count(),
            'cancelled_count' => $cancelledPayments->count(),
            'cash_total' => $payments->where('payment_mode', 'CASH')->sum('amount'),
            'upi_total' => $payments->where('payment_mode', 'UPI')->sum('amount'),
            'books_total' => $payments->sum('books_fee_paid'),
            'tuition_total' => $payments->sum('tuition_fee_paid'),
            'total_collection' => $payments->sum('amount'),
        ];

        $clerks = [];
        if (in_array($role, ['ADMIN', 'ADMINISTRATOR', 'PRINCIPAL', 'CORRESPONDENT'])) {
            $clerks = User::whereHas('role', function($q) {
                $q->where('role_name', 'Clerk');
            })->get();
        }

        $selectedClerk = User::find($clerkId);

        return view('fees.reports.closing', compact('stats', 'payments', 'cancelledPayments', 'dateStr', 'clerks', 'selectedClerk'));
    }

    public function studentReport(): View
    {
        $students = Student::with(['enrollments' => function ($query) {
                $query->with(['academicYear', 'classRoom', 'section'])
                    ->join('academic_years', 'student_enrollments.academic_year_id', '=', 'academic_years.academic_year_id')
                    ->orderByDesc('academic_years.start_date')
                    ->select('student_enrollments.*');
            }])
            ->orderBy('student_name')
            ->get()
            ->map(function (Student $student) {
                // Get the first enrollment which will be the latest due to the ordering in with()
                $enrollment = $student->enrollments->first();

                return [
                    'admission_no' => $student->admission_no,
                    'student_name' => $student->student_name,
                    'class_name' => optional($enrollment?->classRoom)->class_name ?? '-',
                    'section_name' => optional($enrollment?->section)->section_name ?? '-',
                    'status' => $student->status,
                ];
            });

        return view('reports.student-report', compact('students'));
    }

    public function feeReport(Request $request): View
    {
        $date = Carbon::parse($request->get('date', Carbon::today()->toDateString()));

        $payments = Payment::with(['feeAccount.student', 'receipt'])
            ->whereDate('payment_date', $date)
            ->where('status', 'SUCCESS')
            ->orderByDesc('payment_date')
            ->get();

        return view('reports.fee-report', [
            'payments' => $payments,
            'selectedDate' => $date->toDateString(),
        ]);
    }

    public function pendingFeeReport(): View
    {
        $accounts = StudentFeeAccount::with('student')
            ->get()
            ->filter(function($account) {
                return $account->remaining_balance > 0;
            });

        return view('reports.pending-fees', compact('accounts'));
    }

    /**
     * Daily collection report - Restricted to self for Clerks
     */
    public function dailyCollection(Request $request): View
    {
        $role = strtoupper(optional(auth()->user()->role)->role_name ?? '');
        $dateStr = $request->get('date', Carbon::today()->toDateString());
        
        $query = Payment::with(['feeAccount.student', 'receipt', 'collector'])
            ->whereDate('payment_date', $dateStr)
            ->where('status', 'SUCCESS');

        // CLERK Restriction
        if ($role === 'CLERK') {
            $query->where('collected_by', auth()->id());
        }

        $payments = $query->get();
        $totalCollected = $payments->sum('amount');

        return view('fees.reports.daily', compact('payments', 'totalCollected', 'dateStr'));
    }

    /**
     * Output lists of all outstanding fee balances
     */
    public function outstandingFees(Request $request)
    {
        $classes = ClassRoom::all();
        $academicYears = AcademicYear::all();
        
        $activeYear = AcademicYear::where('is_active', true)->first();
        $selectedYearId = $request->get('academic_year_id', $activeYear ? $activeYear->academic_year_id : null);
        $selectedClassId = $request->get('class_id');

        $query = StudentFeeAccount::with(['student', 'academicYear', 'enrollment.classRoom', 'enrollment.section'])
            ->whereHas('enrollment', function($q) use ($selectedYearId, $selectedClassId) {
                $q->where('academic_year_id', $selectedYearId);
                if ($selectedClassId) {
                    $q->where('class_id', $selectedClassId);
                }
            });

        // Search functionality
        if ($request->filled('student_name')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('student_name', 'like', '%' . $request->student_name . '%');
            });
        }

        if ($request->filled('admission_no')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('admission_no', 'like', '%' . $request->admission_no . '%');
            });
        }

        if ($request->filled('father_name')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('father_name', 'like', '%' . $request->father_name . '%');
            });
        }

        if ($request->filled('aadhaar_no')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('aadhaar_no', 'like', '%' . $request->aadhaar_no . '%');
            });
        }

        if ($request->filled('phone_primary')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('phone_primary', 'like', '%' . $request->phone_primary . '%');
            });
        }

        // Fetch all matching accounts to calculate summary stats
        $allMatchingAccounts = $query->get()->filter(function ($account) {
            return (float)$account->remaining_balance > 0;
        });

        $summary = [
            'total_students_with_due' => $allMatchingAccounts->count(),
            'total_outstanding' => $allMatchingAccounts->sum('remaining_balance'),
            'total_books_due' => $allMatchingAccounts->sum('remaining_books_balance'),
            'total_tuition_due' => $allMatchingAccounts->sum('remaining_tuition_balance'),
        ];

        // Manual pagination for the filtered collection
        $perPage = 15;
        $page = $request->get('page', 1);
        $accounts = new \Illuminate\Pagination\LengthAwarePaginator(
            $allMatchingAccounts->forPage($page, $perPage),
            $allMatchingAccounts->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('fees.reports.outstanding', compact(
            'accounts', 
            'classes', 
            'academicYears', 
            'selectedYearId', 
            'selectedClassId', 
            'summary'
        ));
    }

    /**
     * Collection per clerk - Accessible by Correspondent/Admin
     */
    public function clerkCollectionReport(Request $request): View
    {
        // Gated by role middleware in web.php
        $startDate = $request->get('start_date', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::today()->toDateString());

        $clerkStats = Payment::selectRaw('collected_by, SUM(amount) as total_amount, COUNT(payment_id) as receipt_count')
            ->with('collector')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->where('status', 'SUCCESS')
            ->groupBy('collected_by')
            ->get();

        return view('fees.reports.clerk', compact('clerkStats', 'startDate', 'endDate'));
    }
}