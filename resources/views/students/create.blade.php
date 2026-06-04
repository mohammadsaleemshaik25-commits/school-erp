@include('partials.module-nav')

<h1>Add Student</h1>

<a href="/students">
    Back to Students
</a>

<br><br>

<form method="POST" action="/students" enctype="multipart/form-data">
    @csrf

    <input type="text"
        name="admission_no"
        value="{{ old('admission_no', $admissionNo) }}"
        placeholder="Admission Number">

    @error('admission_no')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <input type="text"
        name="student_name"
        value="{{ old('student_name') }}"
        placeholder="Student Name">

    @error('student_name')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <input type="text"
        name="pen_no"
        value="{{ old('pen_no') }}"
        placeholder="PEN Number">

    @error('pen_no')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <input type="text"
        name="aadhaar_no"
        value="{{ old('aadhaar_no') }}"
        placeholder="Aadhaar Number">

    @error('aadhaar_no')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <input type="date"
        name="dob"
        value="{{ old('dob') }}">

    @error('dob')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <select name="gender">
        <option value="">Select Gender</option>
        <option value="Male" @selected(old('gender') === 'Male')>
            Male
        </option>
        <option value="Female" @selected(old('gender') === 'Female')>
            Female
        </option>
        <option value="Other" @selected(old('gender') === 'Other')>
            Other
        </option>
    </select>

    @error('gender')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <input type="text"
        name="father_name"
        value="{{ old('father_name') }}"
        placeholder="Father Name">

    @error('father_name')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <input type="text"
        name="mother_name"
        value="{{ old('mother_name') }}"
        placeholder="Mother Name">

    @error('mother_name')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <input type="text"
        name="guardian_name"
        value="{{ old('guardian_name') }}"
        placeholder="Guardian Name">

    @error('guardian_name')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <input type="text"
        name="phone_primary"
        value="{{ old('phone_primary') }}"
        placeholder="Primary Phone">

    @error('phone_primary')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <input type="text"
        name="phone_secondary"
        value="{{ old('phone_secondary') }}"
        placeholder="Secondary Phone">

    @error('phone_secondary')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <input type="email"
        name="email"
        value="{{ old('email') }}"
        placeholder="Email">

    @error('email')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <textarea name="address"
        placeholder="Address">{{ old('address') }}</textarea>

    @error('address')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <input type="date"
        name="admission_date"
        value="{{ old('admission_date', now()->format('Y-m-d')) }}">

    @error('admission_date')
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

    <label>Student Photo</label>
    <br>
    <input type="file"
        name="photo"
        accept="image/jpeg,image/jpg,image/png">

    @error('photo')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <button type="submit">
        Save
    </button>
</form>
