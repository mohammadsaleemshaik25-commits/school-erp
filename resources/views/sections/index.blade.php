@include('partials.module-nav')

<h1>Sections</h1>

@if (session('success'))
    <div>{{ session('success') }}</div>
@endif

<a href="/sections/create">Add Section</a>

<br><br>

<table border="1" cellpadding="8" cellspacing="0">
    <thead>
        <tr>
            <th>Class</th>
            <th>Section Name</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($sections as $section)
            <tr>
                <td>{{ $section->classRoom?->class_name }}</td>
                <td>{{ $section->section_name }}</td>
                <td>
                    <a href="/sections/{{ $section->section_id }}/edit">Edit</a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3">No sections found.</td>
            </tr>
        @endforelse
    </tbody>
</table>
