<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Http\Request;

class TransferCertificateController extends Controller
{
    public function index(Student $student)
    {
        $transferCertificates = $student->documents()
            ->where('document_type', 'TRANSFER_CERTIFICATE')
            ->latest('uploaded_at')
            ->get();

        return view('students.tc.index', compact('student', 'transferCertificates'));
    }

    public function store(Request $request, Student $student)
    {
        $request->validate([
            'tc_number' => ['required', 'string', 'max:255', 'unique:student_documents,file_name'],
        ]);

        StudentDocument::create([
            'student_id' => $student->student_id,
            'document_type' => 'TRANSFER_CERTIFICATE',
            'file_name' => $request->tc_number,
            'file_path' => null,
            'uploaded_at' => now(),
        ]);

        $student->update([
            'status' => 'TRANSFERRED',
        ]);

        return redirect("/students/{$student->student_id}/tc")
            ->with('message', 'Transfer Certificate generated and student marked as transferred.');
    }

    public function show(Student $student, StudentDocument $document)
    {
        abort_unless($document->student_id === $student->student_id, 404);
        abort_unless($document->document_type === 'TRANSFER_CERTIFICATE', 404);

        $latestEnrollment = $student->enrollments()
            ->with(['academicYear', 'classRoom', 'section'])
            ->latest('created_at')
            ->first();

        return view('students.tc.show', compact('student', 'document', 'latestEnrollment'));
    }
}
