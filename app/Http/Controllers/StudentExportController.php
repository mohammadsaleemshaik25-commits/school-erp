<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentExportController extends Controller
{
    public function studentsExcel(Request $request): StreamedResponse
    {
        return $this->csvDownload(
            $this->buildStudentRows($this->studentsQuery($request)->get()),
            'student-list.csv'
        );
    }

    public function studentsPdf(Request $request)
    {
        $students = $this->studentsQuery($request)->get();

        return view('exports.students-pdf', [
            'title' => 'Student List',
            'students' => $students,
        ]);
    }

    public function passoutExcel(): StreamedResponse
    {
        return $this->csvDownload(
            $this->buildStudentRows($this->passoutQuery()->get()),
            'passout-students.csv'
        );
    }

    public function passoutPdf()
    {
        $students = $this->passoutQuery()->get();

        return view('exports.students-pdf', [
            'title' => 'Passout Students',
            'students' => $students,
        ]);
    }

    public function transferredExcel(): StreamedResponse
    {
        return $this->csvDownload(
            $this->buildStudentRows($this->transferredQuery()->get()),
            'transferred-students.csv'
        );
    }

    public function transferredPdf()
    {
        $students = $this->transferredQuery()->get();

        return view('exports.students-pdf', [
            'title' => 'Transferred Students',
            'students' => $students,
        ]);
    }

    private function studentsQuery(Request $request)
    {
        $search = $request->search;

        return Student::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('student_name', 'like', "%{$search}%")
                        ->orWhere('admission_no', 'like', "%{$search}%");
                });
            })
            ->orderBy('student_name');
    }

    private function passoutQuery()
    {
        return Student::query()
            ->where('status', 'PASSED_OUT')
            ->orderBy('student_name');
    }

    private function transferredQuery()
    {
        return Student::query()
            ->where('status', 'TRANSFERRED')
            ->orderBy('student_name');
    }

    private function buildStudentRows(Collection $students): array
    {
        $rows = [[
            'Admission No',
            'Student Name',
            'Gender',
            'Status',
            'Class',
            'Section',
            'Academic Year',
            'Phone',
        ]];

        foreach ($students as $student) {
            $enrollment = $student->latestEnrollment();

            $rows[] = [
                $student->admission_no,
                $student->student_name,
                $student->gender,
                $student->status,
                $enrollment?->classRoom?->class_name ?? '',
                $enrollment?->section?->section_name ?? '',
                $enrollment?->academicYear?->year_name ?? '',
                $student->phone_primary,
            ];
        }

        return $rows;
    }

    private function csvDownload(array $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
