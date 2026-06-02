<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Section;
use App\Services\PromotionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromotionController extends Controller
{
    public function index()
    {
        $academicYears = AcademicYear::query()
            ->orderByDesc('start_date')
            ->get();

        $classes = ClassRoom::query()
            ->orderBy('display_order')
            ->get();

        $sections = Section::query()
            ->with('classRoom')
            ->orderBy('class_id')
            ->orderBy('section_name')
            ->get();

        return view('promotions.index', compact('academicYears', 'classes', 'sections'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'action_type' => ['required', 'in:PROMOTE,PASSED_OUT'],
            'source_academic_year_id' => ['required', 'exists:academic_years,academic_year_id'],
            'source_class_id' => ['required', 'exists:classes,class_id'],
            'source_section_id' => ['required', 'exists:sections,section_id'],
        ]);

        if ($request->action_type !== 'PASSED_OUT') {
            $request->validate([
                'target_academic_year_id' => ['required', 'exists:academic_years,academic_year_id'],
                'target_class_id' => ['required', 'exists:classes,class_id'],
                'target_section_id' => ['required', 'exists:sections,section_id'],
            ]);

            if ($request->source_academic_year_id === $request->target_academic_year_id
                && $request->source_class_id === $request->target_class_id
                && $request->source_section_id === $request->target_section_id) {
                return back()
                    ->withInput()
                    ->withErrors(['target_section_id' => 'Target must be different from source.']);
            }
        }

        if (! $this->sectionBelongsToClass($request->source_section_id, $request->source_class_id)) {
            return back()
                ->withInput()
                ->withErrors(['source_section_id' => 'Source section does not belong to the selected source class.']);
        }

        if ($request->action_type !== 'PASSED_OUT'
            && ! $this->sectionBelongsToClass($request->target_section_id, $request->target_class_id)) {
            return back()
                ->withInput()
                ->withErrors(['target_section_id' => 'Target section does not belong to the selected target class.']);
        }

        $promotionService = app(PromotionService::class);

        $result = DB::transaction(function () use ($request, $promotionService) {
            return $promotionService->process(
                $request->action_type,
                (int) $request->source_academic_year_id,
                (int) $request->source_class_id,
                (int) $request->source_section_id,
                $request->action_type === 'PASSED_OUT'
                    ? null
                    : (int) $request->target_academic_year_id,
                $request->action_type === 'PASSED_OUT'
                    ? null
                    : (int) $request->target_class_id,
                $request->action_type === 'PASSED_OUT'
                    ? null
                    : (int) $request->target_section_id,
            );
        });

        $actionLabel = str_replace('_', ' ', strtolower($request->action_type));

        return redirect('/promotions')
            ->with('message', "{$result['processed']} students {$actionLabel}. {$result['skipped']} skipped. {$result['total']} source students found.");
    }

    private function sectionBelongsToClass($sectionId, $classId)
    {
        return Section::query()
            ->where('section_id', $sectionId)
            ->where('class_id', $classId)
            ->exists();
    }
}
