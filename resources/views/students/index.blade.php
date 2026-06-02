@include('partials.module-nav')

<h1>Students</h1>

<a href="/students/create">
    Add Student
</a>

<br><br>

<h2>Export</h2>

<p>
    <strong>Student List</strong>
    <a href="/students-export/excel{{ $search ? '?search=' . urlencode($search) : '' }}">Export Excel</a>
    |
    <a href="/students-export/pdf{{ $search ? '?search=' . urlencode($search) : '' }}">Export PDF</a>
</p>

<p>
    <strong>Passout Students</strong>
    <a href="/students-export/passout/excel">Export Excel</a>
    |
    <a href="/students-export/passout/pdf">Export PDF</a>
</p>

<p>
    <strong>Transferred Students</strong>
    <a href="/students-export/transferred/excel">Export Excel</a>
    |
    <a href="/students-export/transferred/pdf">Export PDF</a>
</p>

<br>

<form method="GET" action="/students">
    <input type="text"
        name="search"
        value="{{ $search }}"
        placeholder="Search by name or admission no">

    <button type="submit">
        Search
    </button>

    @if ($search)
        <a href="/students">
            Clear
        </a>
    @endif
</form>

<br>

<table border="1" cellpadding="8" cellspacing="0">
    <thead>
        <tr>
            <th>Admission Number</th>
            <th>Student Name</th>
            <th>Profile</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($students as $student)
            <tr>
                <td>{{ $student->admission_no }}</td>
                <td>{{ $student->student_name }}</td>
                <td>
                    <a href="/students/{{ $student->student_id }}">
                        View
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3">
                    No students found.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
