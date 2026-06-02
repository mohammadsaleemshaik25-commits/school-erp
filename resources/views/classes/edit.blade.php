@include('partials.module-nav')

<h1>Edit Class</h1>

<a href="/classes">Back to Classes</a>

<br><br>

<form method="POST" action="/classes/{{ $class->class_id }}">
    @csrf
    @method('PUT')

    <input type="text" name="class_name" value="{{ old('class_name', $class->class_name) }}" placeholder="Class Name">
    @error('class_name')<div>{{ $message }}</div>@enderror

    <br><br>

    <input type="number" name="display_order" value="{{ old('display_order', $class->display_order) }}" min="1">
    @error('display_order')<div>{{ $message }}</div>@enderror

    <br><br>

    <button type="submit">Update</button>
</form>
