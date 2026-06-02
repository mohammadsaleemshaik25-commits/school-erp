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
        $totalStudents = Student::count();
        $todayCollection = Payment::whereDate('payment_date', today())->sum('amount');
        $pendingFees = StudentFeeAccount::query()
            ->get()
            ->sum(fn (StudentFeeAccount $account) => $account->remaining_balance);
        $academicYears = AcademicYear::count();

        return view('dashboard', compact(
            'totalStudents',
            'todayCollection',
            'pendingFees',
            'academicYears'
        ));
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'total_students' => Student::count(),
            'today_collection' => Payment::whereDate('payment_date', today())->sum('amount'),
            'pending_fees' => StudentFeeAccount::query()
                ->get()
                ->sum(fn (StudentFeeAccount $account) => $account->remaining_balance),
            'active_students' => Student::where('status', 'ACTIVE')->count(),
            'passout_students' => Student::where('status', 'PASSED_OUT')->count(),
            'transferred_students' => Student::where('status', 'TRANSFERRED')->count(),
            'academic_years' => AcademicYear::count(),
        ]);
    }
}
