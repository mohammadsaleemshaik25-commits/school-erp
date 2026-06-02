@include('partials.module-nav')

<h1>Edit Section</h1>

<a href="/sections">Back to Sections</a>

<br><br>

<form method="POST" action="/sections/{{ $section->section_id }}">
    @csrf
    @method('PUT')

    <select name="class_id">
        <option value="">Select Class</option>
        @foreach ($classes as $classRoom)
            <option value="{{ $classRoom->class_id }}" @selected(old('class_id', $section->class_id) == $classRoom->class_id)>
                {{ $classRoom->class_name }}
            </option>
        @endforeach
    </select>
    @error('class_id')<div>{{ $message }}</div>@enderror

    <br><br>

    <input type="text" name="section_name" value="{{ old('section_name', $section->section_name) }}">
    @error('section_name')<div>{{ $message }}</div>@enderror

    <br><br>

    <button type="submit">Update</button>
</form>
