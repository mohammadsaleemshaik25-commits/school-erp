<h1>Academic Years</h1>

@if (session('success'))
    <div>{{ session('success') }}</div>
@endif

@if (session('error'))
    <div>{{ session('error') }}</div>
@endif

<a href="/academic-years/create">
    Add Academic Year
</a>

<br><br>

<table border="1" cellpadding="8" cellspacing="0">
    <thead>
        <tr>
            <th>Year Name</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Active</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($academicYears as $academicYear)
            <tr>
                <td>{{ $academicYear->year_name }}</td>
                <td>{{ $academicYear->start_date?->format('Y-m-d') }}</td>
                <td>{{ $academicYear->end_date?->format('Y-m-d') }}</td>
                <td>{{ $academicYear->is_active ? 'Yes' : 'No' }}</td>
                <td>
                    <form
                        method="POST"
                        action="/academic-years/{{ $academicYear->academic_year_id }}/close">
                        @csrf
                        <button
                            type="submit"
                            onclick="return confirm('Close this academic year? This will create the next year and promote all active students.')">
                            Close Year
                        </button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5">
                    No academic years found.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
