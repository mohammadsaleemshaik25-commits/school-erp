@include('partials.module-nav')

<h1>Classes</h1>

@if (session('success'))
    <div>{{ session('success') }}</div>
@endif

<a href="/classes/create">Add Class</a>

<br><br>

<table border="1" cellpadding="8" cellspacing="0">
    <thead>
        <tr>
            <th>Order</th>
            <th>Class Name</th>
            <th>Sections</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($classes as $class)
            <tr>
                <td>{{ $class->display_order }}</td>
                <td>{{ $class->class_name }}</td>
                <td>{{ $class->sections_count }}</td>
                <td>
                    <a href="/classes/{{ $class->class_id }}/edit">Edit</a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4">No classes found.</td>
            </tr>
        @endforelse
    </tbody>
</table>
