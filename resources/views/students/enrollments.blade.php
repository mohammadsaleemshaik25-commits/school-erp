<h1>Student Enrollments</h1>

<a href="/students/{{ $student->student_id }}">
    Back to Profile
</a>

<br><br>

<h2>{{ $student->student_name }}</h2>

<p>
    Admission Number: {{ $student->admission_no }}
</p>

<form method="POST" action="/students/{{ $student->student_id }}/enrollments">
    @csrf

    <select name="academic_year_id">
        <option value="">Select Academic Year</option>
        @foreach ($academicYears as $academicYear)
            <option value="{{ $academicYear->academic_year_id }}"
                @selected(old('academic_year_id') == $academicYear->academic_year_id)>
                {{ $academicYear->year_name }}
            </option>
        @endforeach
    </select>

    @error('academic_year_id')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <select name="class_id">
        <option value="">Select Class</option>
        @foreach ($classes as $classRoom)
            <option value="{{ $classRoom->class_id }}"
                @selected(old('class_id') == $classRoom->class_id)>
                {{ $classRoom->class_name }}
            </option>
        @endforeach
    </select>

    @error('class_id')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <select name="section_id">
        <option value="">Select Section</option>
        @foreach ($sections as $section)
            <option value="{{ $section->section_id }}"
                @selected(old('section_id') == $section->section_id)>
                {{ $section->section_name }}
            </option>
        @endforeach
    </select>

    @error('section_id')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <input type="text"
        name="promotion_status"
        value="{{ old('promotion_status') }}"
        placeholder="Promotion Status">

    @error('promotion_status')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <select name="status">
        <option value="ACTIVE" @selected(old('status', 'ACTIVE') === 'ACTIVE')>
            ACTIVE
        </option>
        <option value="INACTIVE" @selected(old('status') === 'INACTIVE')>
            INACTIVE
        </option>
    </select>

    @error('status')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <button type="submit">
        Save Enrollment
    </button>
</form>

<br>

<table border="1" cellpadding="8" cellspacing="0">
    <thead>
        <tr>
            <th>Academic Year</th>
            <th>Class</th>
            <th>Section</th>
            <th>Promotion Status</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($enrollments as $enrollment)
            <tr>
                <td>{{ $enrollment->academicYear?->year_name }}</td>
                <td>{{ $enrollment->classRoom?->class_name }}</td>
                <td>{{ $enrollment->section?->section_name }}</td>
                <td>{{ $enrollment->promotion_status }}</td>
                <td>{{ $enrollment->status }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5">
                    No enrollments found.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
