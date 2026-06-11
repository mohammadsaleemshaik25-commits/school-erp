<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Admission;
use App\Models\Student;
use App\Models\ClassRoom;
use App\Models\Section;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
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
            'Mother Name', 'Guardian Name', 'Mobile Number', 'Secondary Phone', 'Email',
            'Address', 'Permanent Address', 'Village', 'District', 'State', 'PIN Code',
            'Nationality', 'Religion', 'Category', 'Blood Group', 'Occupation', 'Annual Income',
            'Previous School', 'Previous Class', 'Class Name', 'Section Name', 'Aadhaar Number', 'PEN Number',
            'Photo File Name (optional)'
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
            'import_file' => 'required|file|mimes:csv,txt,xlsx,xls'
        ]);

        $file = $request->file('import_file');
        $photoFolder = $request->file('photo_folder');

        // Handle Excel files
        if (in_array($file->getClientOriginalExtension(), ['xlsx', 'xls'])) {
            $data = $this->parseExcelFile($file);
        } else {
            // Handle CSV files
            $data = array_map('str_getcsv', file($file->getRealPath()));
            array_shift($data); // Remove header
        }

        $results = [
            'valid' => [],
            'rejected' => [],
            'warnings' => []
        ];

        foreach ($data as $index => $row) {
            if (empty($row) || (is_array($row) && count($row) < 1)) continue;

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
                'phone_secondary' => $row[7] ?? '',
                'email' => $row[8] ?? '',
                'address' => $row[9] ?? '',
                'permanent_address' => $row[10] ?? '',
                'village' => $row[11] ?? '',
                'district' => $row[12] ?? '',
                'state' => $row[13] ?? '',
                'pin_code' => $row[14] ?? '',
                'nationality' => $row[15] ?? 'Indian',
                'religion' => $row[16] ?? '',
                'category' => $row[17] ?? '',
                'blood_group' => $row[18] ?? '',
                'occupation' => $row[19] ?? '',
                'annual_income' => $row[20] ?? '',
                'previous_school' => $row[21] ?? '',
                'previous_class' => $row[22] ?? '',
                'class_name' => $row[23] ?? '',
                'section_name' => $row[24] ?? '',
                'aadhaar_no' => $row[25] ?? '',
                'pen_no' => $row[26] ?? '',
                'photo_file_name' => $row[27] ?? '',
            ];

            $errors = $this->validateRecord($record);

            if (empty($errors)) {
                $results['valid'][] = $record;
            } else {
                $record['errors'] = $errors;
                $results['rejected'][] = $record;
            }
        }

        // Store photo folder path if uploaded
        if ($photoFolder) {
            $photoFolderPath = $photoFolder->store('temp/photos', 'public');
            session(['bulk_import_photo_folder' => $photoFolderPath]);
        }

        session(['bulk_import_data' => $results['valid']]);

        return view('admissions.bulk.preview', compact('results'));
    }

    private function parseExcelFile($file)
    {
        $sheets = Excel::toArray([], $file);

        if (empty($sheets) || !isset($sheets[0])) {
            return [];
        }

        $rows = $sheets[0];

        if (!empty($rows)) {
            array_shift($rows); // Remove header row
        }

        return $rows;
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

    public function confirm(Request $request, \App\Services\AdmissionService $admissionService)
    {
        $data = session('bulk_import_data');
        if (!$data) return redirect()->route('admissions.bulk.index')->with('error', 'No data to import.');

        $ay = AcademicYear::where('is_active', true)->first();
        if (!$ay) return back()->with('error', 'No active academic year found.');

        $photoFolderPath = session('bulk_import_photo_folder');

        $imported = 0;
        $errors = 0;

        DB::beginTransaction();
        try {
            foreach ($data as $record) {
                $class = ClassRoom::where('class_name', $record['class_name'])->first();
                $section = Section::where('class_id', $class->class_id)
                    ->where('section_name', $record['section_name'])
                    ->first();

                // Generate Admission No
                $admissionNo = $admissionService->generateAdmissionNumber();

                // Handle photo mapping
                $photoPath = null;
                if (!empty($record['photo_file_name']) && $photoFolderPath) {
                    $photoFileName = $record['photo_file_name'];
                    $sourcePath = storage_path('app/public/' . $photoFolderPath . '/' . $photoFileName);
                    if (file_exists($sourcePath)) {
                        $destinationPath = 'students/photos/' . $admissionNo . '_' . $photoFileName;
                        Storage::disk('public')->put($destinationPath, file_get_contents($sourcePath));
                        $photoPath = $destinationPath;
                    }
                }

                $student = Student::create([
                    'admission_no' => $admissionNo,
                    'student_name' => $record['student_name'],
                    'gender' => $record['gender'],
                    'dob' => $record['dob'],
                    'nationality' => $record['nationality'] ?? 'Indian',
                    'father_name' => $record['father_name'],
                    'mother_name' => $record['mother_name'],
                    'guardian_name' => $record['guardian_name'],
                    'phone_primary' => $record['phone_primary'],
                    'phone_secondary' => $record['phone_secondary'] ?? null,
                    'email' => $record['email'] ?? null,
                    'address' => $record['address'],
                    'permanent_address' => $record['permanent_address'] ?? null,
                    'village' => $record['village'] ?? null,
                    'district' => $record['district'] ?? null,
                    'state' => $record['state'] ?? null,
                    'pin_code' => $record['pin_code'] ?? null,
                    'religion' => $record['religion'] ?? null,
                    'category' => $record['category'] ?? null,
                    'blood_group' => $record['blood_group'] ?? null,
                    'occupation' => $record['occupation'] ?? null,
                    'annual_income' => $record['annual_income'] ?? null,
                    'previous_school' => $record['previous_school'] ?? null,
                    'previous_class' => $record['previous_class'] ?? null,
                    'aadhaar_no' => $record['aadhaar_no'],
                    'pen_no' => $record['pen_no'],
                    'status' => 'INACTIVE',
                    'admission_date' => now(),
                    'photo_path' => $photoPath,
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

            // Clean up temp photo folder
            if ($photoFolderPath) {
                Storage::disk('public')->deleteDirectory($photoFolderPath);
            }

            DB::commit();
            session()->forget('bulk_import_data');
            session()->forget('bulk_import_photo_folder');

            return redirect()->route('admissions.index')->with('success', "Bulk import complete. {$imported} students imported.");
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Bulk Import Failed: " . $e->getMessage());
            return back()->with('error', "Import failed: " . $e->getMessage());
        }
    }
}
