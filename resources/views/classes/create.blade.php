@include('partials.module-nav')

<h1>Add Class</h1>

<a href="/classes">Back to Classes</a>

<br><br>

<form method="POST" action="/classes">
    @csrf

    <input type="text" name="class_name" value="{{ old('class_name') }}" placeholder="Class Name (e.g. Class 1)">
    @error('class_name')<div>{{ $message }}</div>@enderror

    <br><br>

    <input type="number" name="display_order" value="{{ old('display_order') }}" placeholder="Display Order (1-10)" min="1">
    @error('display_order')<div>{{ $message }}</div>@enderror

    <br><br>

    <button type="submit">Save</button>
</form>
