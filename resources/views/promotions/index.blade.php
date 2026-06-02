<h1>Promotion Module</h1>

@if (session('message'))
    <p>{{ session('message') }}</p>
@endif

<form method="POST" action="/promotions">
    @csrf

    <h2>Source</h2>

    <select name="source_academic_year_id">
        <option value="">Select Academic Year</option>
        @foreach ($academicYears as $academicYear)
            <option value="{{ $academicYear->academic_year_id }}"
                @selected(old('source_academic_year_id') == $academicYear->academic_year_id)>
                {{ $academicYear->year_name }}
            </option>
        @endforeach
    </select>

    @error('source_academic_year_id')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <select name="source_class_id">
        <option value="">Select Class</option>
        @foreach ($classes as $classRoom)
            <option value="{{ $classRoom->class_id }}"
                @selected(old('source_class_id') == $classRoom->class_id)>
                {{ $classRoom->class_name }}
            </option>
        @endforeach
    </select>

    @error('source_class_id')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <select name="source_section_id">
        <option value="">Select Section</option>
        @foreach ($sections as $section)
            <option value="{{ $section->section_id }}"
                @selected(old('source_section_id') == $section->section_id)>
                {{ $section->classRoom?->class_name }} - {{ $section->section_name }}
            </option>
        @endforeach
    </select>

    @error('source_section_id')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <h2>Action</h2>

    <select name="action_type">
        <option value="">Select Action Type</option>
        <option value="PROMOTE" @selected(old('action_type') === 'PROMOTE')>
            Promote
        </option>
        <option value="PASSED_OUT" @selected(old('action_type') === 'PASSED_OUT')>
            Passed Out
        </option>
    </select>

    @error('action_type')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <h2>Target</h2>

    <p>
        Target is required for Promote. It is ignored for Passed Out.
    </p>

    <select name="target_academic_year_id">
        <option value="">Select Academic Year</option>
        @foreach ($academicYears as $academicYear)
            <option value="{{ $academicYear->academic_year_id }}"
                @selected(old('target_academic_year_id') == $academicYear->academic_year_id)>
                {{ $academicYear->year_name }}
            </option>
        @endforeach
    </select>

    @error('target_academic_year_id')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <select name="target_class_id">
        <option value="">Select Class</option>
        @foreach ($classes as $classRoom)
            <option value="{{ $classRoom->class_id }}"
                @selected(old('target_class_id') == $classRoom->class_id)>
                {{ $classRoom->class_name }}
            </option>
        @endforeach
    </select>

    @error('target_class_id')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <select name="target_section_id">
        <option value="">Select Section</option>
        @foreach ($sections as $section)
            <option value="{{ $section->section_id }}"
                @selected(old('target_section_id') == $section->section_id)>
                {{ $section->classRoom?->class_name }} - {{ $section->section_name }}
            </option>
        @endforeach
    </select>

    @error('target_section_id')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <button type="submit">
        Process Students
    </button>
</form>
