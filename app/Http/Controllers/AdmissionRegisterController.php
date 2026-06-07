<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class AdmissionRegisterController extends Controller
{
    public function index(Request $request)
    {
        $fromDate = $request->from_date;
        $toDate = $request->to_date;
        $year = $request->year;

        $admissions = Student::query()
            ->when($fromDate, fn ($q) => $q->whereDate('admission_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('admission_date', '<=', $toDate))
            ->when($year, fn ($q) => $q->whereYear('admission_date', $year))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('student_name', 'like', "%{$search}%")
                        ->orWhere('admission_no', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('admission_date')
            ->orderBy('admission_no')
            ->get();

        $years = Student::query()
            ->selectRaw('YEAR(admission_date) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        return view('admissions.register', compact(
            'admissions',
            'fromDate',
            'toDate',
            'year',
            'years'
        ));
    }
}
