@extends('layouts.app')

@section('title', 'Student Report')

@section('content')
    <div class="bg-white rounded-lg shadow-sm border p-4">
        <div class="mb-4">
            <h2 class="text-xl font-semibold text-slate-900">Student Report</h2>
            <p class="text-sm text-slate-600">Admission, class-section, and student status summary.</p>
        </div>
        <div class="overflow-x-auto rounded border">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="p-2 border text-left">Admission No</th>
                        <th class="p-2 border text-left">Student Name</th>
                        <th class="p-2 border text-left">Class</th>
                        <th class="p-2 border text-left">Section</th>
                        <th class="p-2 border text-left">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        <tr>
                            <td class="p-2 border">{{ $student['admission_no'] }}</td>
                            <td class="p-2 border">{{ $student['student_name'] }}</td>
                            <td class="p-2 border">{{ $student['class_name'] }}</td>
                            <td class="p-2 border">{{ $student['section_name'] }}</td>
                            <td class="p-2 border">{{ $student['status'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-3 text-center text-gray-500">No students found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
