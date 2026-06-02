<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StudentDocumentController extends Controller
{
    public function index(Student $student)
    {
        $documents = $student->documents()
            ->latest('uploaded_at')
            ->get();

        return view('students.documents', compact('student', 'documents'));
    }

    public function store(Request $request, Student $student)
    {
        $request->validate([
            'document_type' => ['required', 'string', 'max:50'],
            'document_file' => ['required', 'file', 'max:2048'],
        ]);

        $file = $request->file('document_file');
        $fileName = Str::slug($request->document_type) . '-' . time() . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs("students/{$student->student_id}/documents", $fileName, 'public');

        StudentDocument::create([
            'student_id' => $student->student_id,
            'document_type' => $request->document_type,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'uploaded_at' => now(),
        ]);

        return redirect("/students/{$student->student_id}/documents");
    }
}
