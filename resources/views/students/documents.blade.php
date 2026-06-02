<h1>Student Documents</h1>

<a href="/students/{{ $student->student_id }}">
    Back to Profile
</a>

<br><br>

<h2>{{ $student->student_name }}</h2>

<p>
    Admission Number: {{ $student->admission_no }}
</p>

<form method="POST"
    action="/students/{{ $student->student_id }}/documents"
    enctype="multipart/form-data">
    @csrf

    <input type="text"
        name="document_type"
        value="{{ old('document_type') }}"
        placeholder="Document Type">

    @error('document_type')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <input type="file"
        name="document_file">

    @error('document_file')
        <div>{{ $message }}</div>
    @enderror

    <br><br>

    <button type="submit">
        Upload Document
    </button>
</form>

<br>

<table border="1" cellpadding="8" cellspacing="0">
    <thead>
        <tr>
            <th>Document Type</th>
            <th>File Name</th>
            <th>Uploaded At</th>
            <th>File</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($documents as $document)
            <tr>
                <td>{{ $document->document_type }}</td>
                <td>{{ $document->file_name }}</td>
                <td>{{ $document->uploaded_at }}</td>
                <td>
                    @if ($document->file_path)
                        <a href="{{ asset('storage/' . $document->file_path) }}" target="_blank">
                            View
                        </a>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4">
                    No documents uploaded.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
