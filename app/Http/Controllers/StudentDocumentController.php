<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentDocumentController extends Controller
{
    public function index(Student $student)
    {
        $documents = $student->documents()
            ->latest('uploaded_at')
            ->get();

        $documentTypes = [
            'TC' => 'Transfer Certificate',
            'AADHAAR' => 'Aadhaar Card',
            'BIRTH_CERTIFICATE' => 'Birth Certificate',
            'CASTE_CERTIFICATE' => 'Caste Certificate',
            'INCOME_CERTIFICATE' => 'Income Certificate',
            'MARKS_MEMO' => 'Marks Memo',
            'STUDY_CERTIFICATE' => 'Study Certificate',
            'OTHER' => 'Other Documents',
        ];

        return view('students.documents', compact('student', 'documents', 'documentTypes'));
    }

    public function store(Request $request, Student $student)
    {
        $request->validate([
            'document_type' => ['required', 'string', 'in:TC,AADHAAR,BIRTH_CERTIFICATE,CASTE_CERTIFICATE,INCOME_CERTIFICATE,MARKS_MEMO,STUDY_CERTIFICATE,OTHER'],
            'document_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $file = $request->file('document_file');
        $fileName = Str::slug($request->document_type) . '_' . time() . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs("students/documents", $fileName, 'public');

        $document = StudentDocument::create([
            'student_id' => $student->student_id,
            'document_type' => $request->document_type,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'uploaded_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $request->document_type . '_UPLOADED',
            'table_name' => 'student_documents',
            'record_id' => $document->document_id,
            'new_value' => json_encode([
                'student_id' => $student->student_id,
                'document_type' => $request->document_type,
                'file_name' => $file->getClientOriginalName(),
            ]),
            'ip_address' => $request->ip(),
        ]);

        return redirect("/students/{$student->student_id}/documents")->with('success', 'Document uploaded successfully.');
    }

    public function show(Student $student, StudentDocument $document)
    {
        if ($document->student_id !== $student->student_id) {
            abort(404);
        }

        return response()->file(storage_path('app/public/' . $document->file_path));
    }

    public function download(Student $student, StudentDocument $document)
    {
        if ($document->student_id !== $student->student_id) {
            abort(404);
        }

        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }

    public function update(Request $request, Student $student, StudentDocument $document)
    {
        if ($document->student_id !== $student->student_id) {
            abort(404);
        }

        $request->validate([
            'document_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $oldFilePath = $document->file_path;
        $oldFileName = $document->file_name;

        $file = $request->file('document_file');
        $fileName = Str::slug($document->document_type) . '_' . time() . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs("students/documents", $fileName, 'public');

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->update([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'uploaded_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'DOCUMENT_REPLACED',
            'table_name' => 'student_documents',
            'record_id' => $document->document_id,
            'old_value' => json_encode([
                'file_name' => $oldFileName,
                'file_path' => $oldFilePath,
            ]),
            'new_value' => json_encode([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
            ]),
            'ip_address' => $request->ip(),
        ]);

        return redirect("/students/{$student->student_id}/documents")->with('success', 'Document replaced successfully.');
    }

    public function destroy(Student $student, StudentDocument $document)
    {
        if ($document->student_id !== $student->student_id) {
            abort(404);
        }

        $documentData = [
            'document_type' => $document->document_type,
            'file_name' => $document->file_name,
            'file_path' => $document->file_path,
        ];

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'DOCUMENT_DELETED',
            'table_name' => 'student_documents',
            'record_id' => $document->document_id,
            'old_value' => json_encode($documentData),
            'ip_address' => request()->ip(),
        ]);

        return redirect("/students/{$student->student_id}/documents")->with('success', 'Document deleted successfully.');
    }
}
