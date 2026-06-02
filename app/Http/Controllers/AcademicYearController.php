<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Services\PromotionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcademicYearController extends Controller
{
    public function index()
    {
        $academicYears = AcademicYear::query()
            ->orderByDesc('start_date')
            ->get();

        return view('academic-years.index', compact('academicYears'));
    }

    public function create()
    {
        return view('academic-years.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'year_name' => ['required', 'string', 'max:20', 'unique:academic_years,year_name'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        AcademicYear::create([
            'year_name' => $request->year_name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect('/academic-years');
    }

    public function closeYear(AcademicYear $academicYear, PromotionService $promotionService)
    {
        $startYear = $academicYear->start_date->year + 1;
        $endYear = $academicYear->end_date->year + 1;
        $nextYearName = $startYear . '-' . $endYear;

        $existingYear = AcademicYear::where('year_name', $nextYearName)->first();

        if ($existingYear) {
            return back()->with(
                'error',
                'Next Academic Year already exists.'
            );
        }

        $result = DB::transaction(function () use ($academicYear, $promotionService, $nextYearName, $startYear, $endYear) {
            $newYear = AcademicYear::create([
                'year_name' => $nextYearName,
                'start_date' => $startYear . '-06-01',
                'end_date' => $endYear . '-05-31',
                'is_active' => true,
            ]);

            $academicYear->update(['is_active' => false]);

            AcademicYear::query()
                ->where('academic_year_id', '!=', $newYear->academic_year_id)
                ->update(['is_active' => false]);

            return $promotionService->runAcademicYearClosing($academicYear, $newYear);
        });

        $message = sprintf(
            'Academic Year closed successfully. %d students promoted, %d passed out, %d skipped.',
            $result['promoted'],
            $result['passedOut'],
            $result['skipped']
        );

        return redirect('/academic-years')->with('success', $message);
    }
}
