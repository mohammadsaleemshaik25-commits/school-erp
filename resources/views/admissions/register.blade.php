@include('partials.module-nav')

<h1>Admission Register</h1>

<p>Official record of all student admissions.</p>

<form method="GET" action="/admissions/register">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or admission no">

    <input type="date" name="from_date" value="{{ $fromDate }}" placeholder="From date">

    <input type="date" name="to_date" value="{{ $toDate }}" placeholder="To date">

    <select name="year">
        <option value="">All Years</option>
        @foreach ($years as $admissionYear)
            <option value="{{ $admissionYear }}" @selected($year == $admissionYear)>
                {{ $admissionYear }}
            </option>
        @endforeach
    </select>

    <button type="submit">Filter</button>

    <a href="/admissions/register">Clear</a>
</form>

<br>

<table border="1" cellpadding="8" cellspacing="0">
    <thead>
        <tr>
            <th>#</th>
            <th>Admission No</th>
            <th>Student Name</th>
            <th>Admission Date</th>
            <th>Father Name</th>
            <th>Phone</th>
            <th>Status</th>
            <th>Profile</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($admissions as $index => $student)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $student->admission_no }}</td>
                <td>{{ $student->student_name }}</td>
                <td>{{ $student->admission_date }}</td>
                <td>{{ $student->father_name }}</td>
                <td>{{ $student->phone_primary }}</td>
                <td>{{ $student->status }}</td>
                <td>
                    <a href="/students/{{ $student->student_id }}">View</a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8">No admissions found.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<p><strong>Total:</strong> {{ $admissions->count() }} admissions</p>
