<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Student;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class AdmissionPhotoSyncController extends Controller
{
    public function index()
    {
        $photoDirectory = storage_path('app/public/students/photos');
        if (!File::exists($photoDirectory)) {
            File::makeDirectory($photoDirectory, 0755, true);
        }

        $files = File::files($photoDirectory);
        $fileNames = array_map(fn($f) => $f->getFilename(), $files);

        $students = Student::whereNotNull('photo_path')->get();
        $mappedFiles = $students->pluck('photo_path')->map(fn($p) => basename($p))->toArray();

        $stats = [
            'total_files' => count($fileNames),
            'matched' => 0,
            'missing' => 0,
            'orphans' => [],
        ];

        $matchedMap = [];
        foreach ($fileNames as $file) {
            if (in_array($file, $mappedFiles)) {
                $stats['matched']++;
                $matchedMap[] = $file;
            } else {
                $stats['orphans'][] = $file;
            }
        }

        $stats['missing'] = Student::whereNull('photo_path')
            ->orWhere('photo_path', '')
            ->count();

        return view('admissions.photo-sync.index', compact('stats', 'fileNames'));
    }

    public function sync()
    {
        $photoDirectory = storage_path('app/public/students/photos');
        $files = File::files($photoDirectory);
        
        $syncedCount = 0;
        foreach ($files as $file) {
            $filename = $file->getFilename();
            // Try to match by admission number (e.g., ADM001.jpg)
            $admissionNo = pathinfo($filename, PATHINFO_FILENAME);
            
            $student = Student::where('admission_no', $admissionNo)->first();
            if ($student && (empty($student->photo_path) || $student->photo_path !== 'students/photos/' . $filename)) {
                $student->update(['photo_path' => 'students/photos/' . $filename]);
                $syncedCount++;
            }
        }

        return redirect()->back()->with('success', "Photo synchronization complete. {$syncedCount} student records updated.");
    }
}
