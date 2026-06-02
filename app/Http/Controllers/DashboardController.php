<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Student;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        return response()->json([
            'total_students' => Student::count(),
            'active_students' => Student::where('status', 'ACTIVE')->count(),
            'passout_students' => Student::where('status', 'PASSED_OUT')->count(),
            'transferred_students' => Student::where('status', 'TRANSFERRED')->count(),
            'academic_years' => AcademicYear::count(),
        ]);
    }
}
