<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentFeeAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $role = strtoupper(optional(auth()->user()->role)->role_name ?? '');

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

        // Existing logic for other roles
        $totalStudents = Student::count();
        $todayCollection = Payment::whereDate('payment_date', today())->sum('amount');
        
        // Calculate pending fees by summing (total_due - successful payments)
        $pendingFees = StudentFeeAccount::get()->sum(function($account) {
            return $account->remaining_balance;
        });
        
        $academicYears = AcademicYear::count();

        return view('dashboard', compact(
            'totalStudents',
            'todayCollection',
            'pendingFees',
            'academicYears',
            'role'
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
