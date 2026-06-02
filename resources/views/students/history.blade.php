@include('partials.module-nav')

<h1>Student History</h1>

<a href="/students/{{ $student->student_id }}">Back to Profile</a>

<br><br>

<h2>{{ $student->student_name }} ({{ $student->admission_no }})</h2>

<table border="1" cellpadding="8" cellspacing="0">
    <thead>
        <tr>
            <th>Academic Year</th>
            <th>Class</th>
            <th>Section</th>
            <th>Status</th>
            <th>Promotion Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($enrollmentHistory as $enrollment)
            <tr>
                <td>{{ $enrollment->academicYear?->year_name }}</td>
                <td>{{ $enrollment->classRoom?->class_name }}</td>
                <td>{{ $enrollment->section?->section_name }}</td>
                <td>{{ $enrollment->status }}</td>
                <td>{{ $enrollment->promotion_status }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5">No enrollment history found.</td>
            </tr>
        @endforelse
    </tbody>
</table>
