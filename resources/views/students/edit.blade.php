@include('partials.module-nav')

<h1>Edit Student</h1>

<a href="/students/{{ $student->student_id }}">Back to Profile</a>

<br><br>

<form method="POST" action="/students/{{ $student->student_id }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <input type="text" name="admission_no" value="{{ old('admission_no', $student->admission_no) }}" placeholder="Admission Number">
    @error('admission_no') <div>{{ $message }}</div> @enderror
    <br><br>

    <input type="text" name="student_name" value="{{ old('student_name', $student->student_name) }}" placeholder="Student Name">
    @error('student_name') <div>{{ $message }}</div> @enderror
    <br><br>

    <input type="text" name="pen_no" value="{{ old('pen_no', $student->pen_no) }}" placeholder="PEN Number">
    @error('pen_no') <div>{{ $message }}</div> @enderror
    <br><br>

    <input type="text" name="aadhaar_no" value="{{ old('aadhaar_no', $student->aadhaar_no) }}" placeholder="Aadhaar Number">
    @error('aadhaar_no') <div>{{ $message }}</div> @enderror
    <br><br>

    <input type="date" name="dob" value="{{ old('dob', $student->dob) }}">
    @error('dob') <div>{{ $message }}</div> @enderror
    <br><br>

    <select name="gender">
        <option value="Male" @selected(old('gender', $student->gender) === 'Male')>Male</option>
        <option value="Female" @selected(old('gender', $student->gender) === 'Female')>Female</option>
        <option value="Other" @selected(old('gender', $student->gender) === 'Other')>Other</option>
    </select>
    @error('gender') <div>{{ $message }}</div> @enderror
    <br><br>

    <input type="text" name="father_name" value="{{ old('father_name', $student->father_name) }}" placeholder="Father Name">
    @error('father_name') <div>{{ $message }}</div> @enderror
    <br><br>

    <input type="text" name="mother_name" value="{{ old('mother_name', $student->mother_name) }}" placeholder="Mother Name">
    @error('mother_name') <div>{{ $message }}</div> @enderror
    <br><br>

    <input type="text" name="guardian_name" value="{{ old('guardian_name', $student->guardian_name) }}" placeholder="Guardian Name">
    @error('guardian_name') <div>{{ $message }}</div> @enderror
    <br><br>

    <input type="text" name="phone_primary" value="{{ old('phone_primary', $student->phone_primary) }}" placeholder="Primary Phone">
    @error('phone_primary') <div>{{ $message }}</div> @enderror
    <br><br>

    <input type="text" name="phone_secondary" value="{{ old('phone_secondary', $student->phone_secondary) }}" placeholder="Secondary Phone">
    @error('phone_secondary') <div>{{ $message }}</div> @enderror
    <br><br>

    <input type="email" name="email" value="{{ old('email', $student->email) }}" placeholder="Email">
    @error('email') <div>{{ $message }}</div> @enderror
    <br><br>

    <textarea name="address" placeholder="Address">{{ old('address', $student->address) }}</textarea>
    @error('address') <div>{{ $message }}</div> @enderror
    <br><br>

    <input type="date" name="admission_date" value="{{ old('admission_date', $student->admission_date) }}">
    @error('admission_date') <div>{{ $message }}</div> @enderror
    <br><br>

    <select name="status">
        <option value="ACTIVE" @selected(old('status', $student->status) === 'ACTIVE')>ACTIVE</option>
        <option value="INACTIVE" @selected(old('status', $student->status) === 'INACTIVE')>INACTIVE</option>
        <option value="PASSED_OUT" @selected(old('status', $student->status) === 'PASSED_OUT')>PASSED_OUT</option>
        <option value="TRANSFERRED" @selected(old('status', $student->status) === 'TRANSFERRED')>TRANSFERRED</option>
    </select>
    @error('status') <div>{{ $message }}</div> @enderror
    <br><br>

    <label>Student Photo</label>
    <br>
    @if ($student->photo_path)
        <img src="{{ asset('storage/' . $student->photo_path) }}" alt="Current Photo" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-bottom: 10px;">
        <br>
    @endif
    <input type="file" name="photo" accept="image/jpeg,image/jpg,image/png">
    @error('photo') <div>{{ $message }}</div> @enderror
    <br><br>

    <button type="submit">Update</button>
</form>
