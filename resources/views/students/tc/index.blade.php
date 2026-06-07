<h1>Transfer Certificate</h1>

<a href="/students/{{ $student->student_id }}">
    Back to Profile
</a>

<br><br>

@if (session('message'))
    <p>{{ session('message') }}</p>
@endif

<h2>{{ $student->student_name }}</h2>

<p>
    Admission Number: {{ $student->admission_no }}
</p>

<p>
    Current Status: {{ $student->status }}
</p>

<form method="POST" action="/students/{{ $student->student_id }}/tc">
    @csrf

    <input type="text"
        name="tc_number"
        value="{{ old('tc_number') }}"
        placeholder="TC Number">

    @error('tc_number')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <button type="submit">
        Generate TC
    </button>
</form>

<br>

<table border="1" cellpadding="8" cellspacing="0">
    <thead>
        <tr>
            <th>TC Number</th>
            <th>Generated At</th>
            <th>Download</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($transferCertificates as $certificate)
            <tr>
                <td>{{ $certificate->file_name }}</td>
                <td>{{ $certificate->uploaded_at }}</td>
                <td>
                    <a href="/students/{{ $student->student_id }}/tc/{{ $certificate->document_id }}" target="_blank">
                        Download TC
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3">
                    No transfer certificate generated.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
