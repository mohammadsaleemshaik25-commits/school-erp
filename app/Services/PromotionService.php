<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Section;
use App\Models\StudentEnrollment;

class PromotionService
{
    public function process(
        string $actionType,
        int $sourceAcademicYearId,
        int $sourceClassId,
        int $sourceSectionId,
        ?int $targetAcademicYearId = null,
        ?int $targetClassId = null,
        ?int $targetSectionId = null,
    ): array {
        $sourceEnrollments = StudentEnrollment::query()
            ->where('academic_year_id', $sourceAcademicYearId)
            ->where('class_id', $sourceClassId)
            ->where('section_id', $sourceSectionId)
            ->where('status', 'ACTIVE')
            ->get();

        $processed = 0;
        $skipped = 0;

        foreach ($sourceEnrollments as $sourceEnrollment) {
            if ($actionType === 'PASSED_OUT') {
                $sourceEnrollment->update([
                    'promotion_status' => 'PASSED_OUT',
                    'status' => 'INACTIVE',
                ]);

                $sourceEnrollment->student?->update([
                    'status' => 'PASSED_OUT',
                ]);

                $processed++;
                continue;
            }

            $alreadyEnrolled = StudentEnrollment::query()
                ->where('student_id', $sourceEnrollment->student_id)
                ->where('academic_year_id', $targetAcademicYearId)
                ->exists();

            if ($alreadyEnrolled) {
                $skipped++;
                continue;
            }

            StudentEnrollment::create([
                'student_id' => $sourceEnrollment->student_id,
                'academic_year_id' => $targetAcademicYearId,
                'class_id' => $targetClassId,
                'section_id' => $targetSectionId,
                'promotion_status' => 'PROMOTED',
                'status' => 'ACTIVE',
            ]);

            $sourceEnrollment->update([
                'promotion_status' => 'PROMOTED',
                'status' => 'INACTIVE',
            ]);

            $processed++;
        }

        return [
            'processed' => $processed,
            'skipped' => $skipped,
            'total' => $sourceEnrollments->count(),
        ];
    }

    public function runAcademicYearClosing(
        AcademicYear $closingYear,
        AcademicYear $newYear
    ): array {
        $classes = ClassRoom::query()
            ->orderBy('display_order')
            ->get();

        $processed = 0;
        $skipped = 0;
        $total = 0;
        $promoted = 0;
        $passedOut = 0;

        if ($classes->isEmpty()) {
            return compact('processed', 'skipped', 'total', 'promoted', 'passedOut');
        }

        $finalClass = $classes->last();

        foreach ($classes as $index => $class) {
            $sections = Section::query()
                ->where('class_id', $class->class_id)
                ->get();

            foreach ($sections as $section) {
                if ($class->class_id === $finalClass->class_id) {
                    $result = $this->process(
                        'PASSED_OUT',
                        $closingYear->academic_year_id,
                        $class->class_id,
                        $section->section_id,
                    );
                    $passedOut += $result['processed'];
                } else {
                    $nextClass = $classes[$index + 1];
                    $targetSection = Section::query()
                        ->where('class_id', $nextClass->class_id)
                        ->where('section_name', $section->section_name)
                        ->first();

                    if (! $targetSection) {
                        continue;
                    }

                    $result = $this->process(
                        'PROMOTE',
                        $closingYear->academic_year_id,
                        $class->class_id,
                        $section->section_id,
                        $newYear->academic_year_id,
                        $nextClass->class_id,
                        $targetSection->section_id,
                    );
                    $promoted += $result['processed'];
                }

                $processed += $result['processed'];
                $skipped += $result['skipped'];
                $total += $result['total'];
            }
        }

        return compact('processed', 'skipped', 'total', 'promoted', 'passedOut');
    }
}
