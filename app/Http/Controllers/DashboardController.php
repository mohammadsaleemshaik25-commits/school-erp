<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentFeeAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index()
    {
        $role = strtoupper(optional(auth()->user()->role)->role_name ?? '');

        // RBAC: Clerks should not see high-level management stats
        if ($role === 'CLERK') {
             $clerkId = auth()->id();
             
             // Clerk-specific metrics
             $todayCollection = Payment::whereDate('payment_date', today())
                 ->where('collected_by', $clerkId)
                 ->where('status', 'SUCCESS')
                 ->sum('amount');
                 
             $clerkReceipts = Payment::whereDate('payment_date', today())
                 ->where('collected_by', $clerkId)
                 ->where('status', 'SUCCESS')
                 ->count();
                 
             $cancelledReceipts = Payment::whereDate('payment_date', today())
                 ->where('collected_by', $clerkId)
                 ->where('status', 'CANCELLED')
                 ->count();
 
             $transactionCount = Payment::whereDate('payment_date', today())
                 ->where('collected_by', $clerkId)
                 ->count();
 
             $recentTransactions = Payment::with([
                 'feeAccount.enrollment.student',
                 'receipt'
                 ])
                 ->where('collected_by', $clerkId)
                 ->orderByDesc('created_at')
                 ->limit(10)
                 ->get();
 
             return view('dashboard', compact(
                 'todayCollection',
                 'clerkReceipts',
                 'cancelledReceipts',
                 'transactionCount',
                 'recentTransactions',
                 'role'
             ));
        }

        $activeYear = AcademicYear::where('is_active', true)->first();
        if (!$activeYear) {
            return view('dashboard.no-active-year');
        }

        $todayRevenue = Payment::where('status', 'SUCCESS')
            ->whereDate('payment_date', today())
            ->sum('amount');

        $stats = [
            'total_students' => StudentEnrollment::where('academic_year_id', $activeYear->academic_year_id)->where('status', 'ACTIVE')->count(),
            'total_revenue' => Payment::where('status', 'SUCCESS')->whereHas('feeAccount.enrollment', function($q) use ($activeYear) {
                $q->where('academic_year_id', $activeYear->academic_year_id);
            })->sum('amount'),
            'total_outstanding' => StudentFeeAccount::whereHas('enrollment', function($q) use ($activeYear) {
                $q->where('academic_year_id', $activeYear->academic_year_id);
            })->get()->sum('remaining_balance'),
            'pending_books' => StudentFeeAccount::where('books_status', 'PENDING')->whereHas('enrollment', function($q) use ($activeYear) {
                $q->where('academic_year_id', $activeYear->academic_year_id);
            })->count(),
            'pending_concessions' => \App\Models\StudentFeeAdjustment::where('approval_status', 'PENDING')->count(),
        ];

        $totalStudents = $stats['total_students'];
$totalRevenue = $stats['total_revenue'];
$pendingFees = $stats['total_outstanding'];
$academicYears = AcademicYear::count();
$currentDate = today()->format('d M Y');

return view('dashboard', compact(
    'stats',
    'activeYear',
    'role',
    'totalStudents',
    'totalRevenue',
    'todayRevenue',
    'pendingFees',
    'academicYears',
    'currentDate'
));
    }

    public function stats(): JsonResponse
    {
        $pendingFees = StudentFeeAccount::get()->sum(function($account) {
            return $account->remaining_balance;
        });

        return response()->json([
            'total_students' => Student::count(),
            'today_collection' => Payment::whereDate('payment_date', today())->sum('amount'),
            'pending_fees' => $pendingFees,
            'active_students' => Student::where('status', 'ACTIVE')->count(),
            'passout_students' => Student::where('status', 'PASSED_OUT')->count(),
            'transferred_students' => Student::where('status', 'TRANSFERRED')->count(),
            'academic_years' => AcademicYear::count(),
        ]);
    }
}
