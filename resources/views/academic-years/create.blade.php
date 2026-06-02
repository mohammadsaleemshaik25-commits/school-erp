<h1>Add Academic Year</h1>

<a href="/academic-years">
    Back to Academic Years
</a>

<br><br>

<form method="POST" action="/academic-years">
    @csrf

    <input type="text"
        name="year_name"
        value="{{ old('year_name') }}"
        placeholder="Year Name">

    @error('year_name')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <input type="date"
        name="start_date"
        value="{{ old('start_date') }}">

    @error('start_date')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <input type="date"
        name="end_date"
        value="{{ old('end_date') }}">

    @error('end_date')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <label>
        <input type="checkbox"
            name="is_active"
            value="1"
            @checked(old('is_active'))>
        Active
    </label>

    @error('is_active')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <button type="submit">
        Save
    </button>
</form>
