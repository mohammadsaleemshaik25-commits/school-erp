<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 24px; }
        h1 { font-size: 18px; margin-bottom: 8px; }
        .meta { color: #555; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 6px 8px; text-align: left; }
        th { background: #eee; }
        .toolbar { margin-bottom: 16px; }
        @media print { .toolbar { display: none; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print / Save PDF</button>
        <a href="/students">Back to Students</a>
    </div>

    <h1>VIKAS SCHOOL</h1>
    <p class="meta">{{ $title }} — Generated {{ now()->format('d M Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Admission No</th>
                <th>Student Name</th>
                <th>Gender</th>
                <th>Status</th>
                <th>Class</th>
                <th>Section</th>
                <th>Academic Year</th>
                <th>Phone</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($students as $student)
                @php $enrollment = $student->latestEnrollment(); @endphp
                <tr>
                    <td>{{ $student->admission_no }}</td>
                    <td>{{ $student->student_name }}</td>
                    <td>{{ $student->gender }}</td>
                    <td>{{ $student->status }}</td>
                    <td>{{ $enrollment?->classRoom?->class_name }}</td>
                    <td>{{ $enrollment?->section?->section_name }}</td>
                    <td>{{ $enrollment?->academicYear?->year_name }}</td>
                    <td>{{ $student->phone_primary }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
