<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\StudentFeeAccount;
use App\Models\ClassRoom;
use App\Models\AcademicYear;
use App\Models\Student;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function studentReport(): View
    {
        $students = Student::query()
            ->orderBy('student_name')
            ->get()
            ->map(function (Student $student) {
                $enrollment = $student->currentEnrollment() ?? $student->latestEnrollment();

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
            ->filter(fn (StudentFeeAccount $account) => $account->remaining_balance > 0);

        return view('reports.pending-fees', compact('accounts'));
    }

    /**
     * Display fee collections on a specific date
     */
    public function dailyCollection(Request $request)
    {
        $dateStr = $request->get('date', Carbon::today()->toDateString());
        $date = Carbon::parse($dateStr);

        $payments = Payment::with(['feeAccount.student', 'collector', 'receipt'])
            ->whereDate('payment_date', $date)
            ->where('status', 'SUCCESS')
            ->get();

        $totalCollected = $payments->sum('amount');

        return view('reports.daily-collection', compact('payments', 'totalCollected', 'dateStr'));
    }

    /**
     * Output lists of all outstanding fee balances
     */
    public function outstandingFees(Request $request)
    {
        $classes = ClassRoom::all();
        $academicYears = AcademicYear::all();
        
        $activeYear = AcademicYear::where('is_active', true)->first();
        $selectedYearId = $request->get('academic_year_id', $activeYear ? $activeYear->id : null);
        $selectedClassId = $request->get('class_id');

        $query = StudentFeeAccount::with(['student', 'academicYear'])
            ->where('academic_year_id', $selectedYearId);

        if ($selectedClassId) {
            $query->where('class_id', $selectedClassId);
        }

        // Fetch and filter accounts locally to accurately handle computed outstanding balances
        $accounts = $query->get()->filter(function ($account) {
            return $account->remaining_balance > 0;
        });

        $totalOutstanding = $accounts->sum(function ($account) {
            return $account->remaining_balance;
        });

        return view('fees.reports.outstanding', compact('accounts', 'classes', 'academicYears', 'selectedYearId', 'selectedClassId', 'totalOutstanding'));
    }

    /**
     * Report showing payment collection summary broken down by clerks
     */
    public function clerkCollectionReport(Request $request)
    {
        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : Carbon::today()->startOfDay();
        $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : Carbon::today()->endOfDay();

        $collections = Payment::selectRaw('collected_by, SUM(amount) as total_collected, COUNT(id) as transaction_count')
            ->with('collector')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->where('status', 'SUCCESS')
            ->groupBy('collected_by')
            ->get();

        return view('fees.reports.clerk', compact('collections', 'startDate', 'endDate'));
    }
}