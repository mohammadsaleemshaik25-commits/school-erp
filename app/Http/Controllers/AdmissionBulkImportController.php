<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Admission;
use App\Models\Student;
use App\Models\ClassRoom;
use App\Models\Section;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class AdmissionBulkImportController extends Controller
{
    public function index()
    {
        return view('admissions.bulk.index');
    }

    public function downloadTemplate()
    {
        $headers = [
            'Student Name', 'Gender', 'Date of Birth (YYYY-MM-DD)', 'Father Name', 
            'Mother Name', 'Guardian Name', 'Mobile Number', 'Address', 
            'Class Name', 'Section Name', 'Aadhaar Number', 'PEN Number'
        ];

        $callback = function() use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fclose($file);
        };

        return response()->stream($callback, 200, [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=admission_template.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt'
        ]);

        $file = $request->file('import_file');
        $data = array_map('str_getcsv', file($file->getRealPath()));
        $header = array_shift($data);

        $results = [
            'valid' => [],
            'rejected' => [],
            'warnings' => []
        ];

        foreach ($data as $index => $row) {
            if (count($row) < 1) continue;
            
            // Map row to named array
            $record = [
                'row_index' => $index + 2,
                'student_name' => $row[0] ?? '',
                'gender' => strtoupper($row[1] ?? ''),
                'dob' => $row[2] ?? '',
                'father_name' => $row[3] ?? '',
                'mother_name' => $row[4] ?? '',
                'guardian_name' => $row[5] ?? '',
                'phone_primary' => $row[6] ?? '',
                'address' => $row[7] ?? '',
                'class_name' => $row[8] ?? '',
                'section_name' => $row[9] ?? '',
                'aadhaar_no' => $row[10] ?? '',
                'pen_no' => $row[11] ?? '',
            ];

            $errors = $this->validateRecord($record);

            if (empty($errors)) {
                $results['valid'][] = $record;
            } else {
                $record['errors'] = $errors;
                $results['rejected'][] = $record;
            }
        }

        session(['bulk_import_data' => $results['valid']]);

        return view('admissions.bulk.preview', compact('results'));
    }

    private function validateRecord($record)
    {
        $errors = [];

        if (empty($record['student_name'])) $errors[] = "Missing Student Name";
        if (!in_array($record['gender'], ['MALE', 'FEMALE', 'OTHER'])) $errors[] = "Invalid Gender (Use MALE/FEMALE/OTHER)";
        
        // Validate Date
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $record['dob'])) {
            $errors[] = "Invalid Date of Birth format (Use YYYY-MM-DD)";
        }

        // Validate Class
        $class = ClassRoom::where('class_name', $record['class_name'])->first();
        if (!$class) $errors[] = "Invalid Class Name: {$record['class_name']}";

        // Validate Section (Optional)
        if (!empty($record['section_name']) && $class) {
            $section = Section::where('class_id', $class->class_id)
                ->where('section_name', $record['section_name'])
                ->first();
            if (!$section) $errors[] = "Invalid Section for Class: {$record['section_name']}";
        }

        // Duplicate Check (Aadhaar)
        if (!empty($record['aadhaar_no'])) {
            if (Student::where('aadhaar_no', $record['aadhaar_no'])->exists()) {
                $errors[] = "Duplicate Aadhaar Number";
            }
        }

        return $errors;
    }

    public function confirm(Request $request)
    {
        $data = session('bulk_import_data');
        if (!$data) return redirect()->route('admissions.bulk.index')->with('error', 'No data to import.');

        $ay = AcademicYear::where('is_active', true)->first();
        if (!$ay) return back()->with('error', 'No active academic year found.');

        $imported = 0;
        $errors = 0;

        DB::beginTransaction();
        try {
            foreach ($data as $record) {
                $class = ClassRoom::where('class_name', $record['class_name'])->first();
                $section = Section::where('class_id', $class->class_id)
                    ->where('section_name', $record['section_name'])
                    ->first();

                // Generate Admission No (Simulated or via Service)
                $admissionNo = 'ADM' . date('Y') . str_pad(Student::count() + 1, 4, '0', STR_PAD_LEFT);

                $student = Student::create([
                    'admission_no' => $admissionNo,
                    'student_name' => $record['student_name'],
                    'gender' => $record['gender'],
                    'dob' => $record['dob'],
                    'father_name' => $record['father_name'],
                    'mother_name' => $record['mother_name'],
                    'guardian_name' => $record['guardian_name'],
                    'phone_primary' => $record['phone_primary'],
                    'address' => $record['address'],
                    'aadhaar_no' => $record['aadhaar_no'],
                    'pen_no' => $record['pen_no'],
                    'status' => 'INACTIVE',
                    'admission_date' => now(),
                ]);

                Admission::create([
                    'student_id' => $student->student_id,
                    'academic_year_id' => $ay->academic_year_id,
                    'class_id' => $class->class_id,
                    'section_id' => $section->section_id ?? null,
                    'admission_status' => 'SUBMITTED',
                    'created_by' => auth()->id(),
                ]);

                $imported++;
            }
            DB::commit();
            session()->forget('bulk_import_data');

            return redirect()->route('admissions.index')->with('success', "Bulk import complete. {$imported} students imported.");
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Bulk Import Failed: " . $e->getMessage());
            return back()->with('error', "Import failed: " . $e->getMessage());
        }
    }
}
