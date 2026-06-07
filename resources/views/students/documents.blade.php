@include('partials.module-nav')

<h1>Student Documents</h1>

<a href="/students/{{ $student->student_id }}">
    Back to Profile
</a>

<br><br>

<h2>{{ $student->student_name }}</h2>

<p>
    Admission Number: {{ $student->admission_no }}
</p>

@if (session('success'))
    <div style="color: green; margin-bottom: 10px;">
        {{ session('success') }}
    </div>
@endif

<!-- Student Photo Section -->
<div style="border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 8px;">
    <h3>Student Photo</h3>
    <div style="display: flex; align-items: center; gap: 20px;">
        @if ($student->photo_path)
            <img src="{{ asset('storage/' . $student->photo_path) }}" alt="Student Photo" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 2px solid #ddd;">
        @else
            <div style="width: 100px; height: 100px; border-radius: 50%; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center; border: 2px solid #ddd;">
                No Photo
            </div>
        @endif
        <div>
            <a href="/students/{{ $student->student_id }}/edit" style="text-decoration: none; color: #0066cc;">
                {{ $student->photo_path ? 'Replace Photo' : 'Upload Photo' }}
            </a>
        </div>
    </div>
</div>

<!-- Upload Document Form -->
<div style="border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 8px;">
    <h3>Upload New Document</h3>
    <form method="POST"
        action="/students/{{ $student->student_id }}/documents"
        enctype="multipart/form-data">
        @csrf

        <label>Document Type</label>
        <br>
        <select name="document_type" style="padding: 8px; margin-bottom: 10px; width: 300px;">
            <option value="">Select Document Type</option>
            @foreach ($documentTypes as $key => $label)
                <option value="{{ $key }}" @selected(old('document_type') === $key)>
                    {{ $label }}
                </option>
            @endforeach
        </select>

        @error('document_type')
            <div style="color: red;">{{ $message }}</div>
        @enderror

        <br><br>

        <label>Document File (JPG, JPEG, PNG, PDF - Max 5MB)</label>
        <br>
        <input type="file"
            name="document_file"
            accept=".jpg,.jpeg,.png,.pdf"
            style="margin-bottom: 10px;">

        @error('document_file')
            <div style="color: red;">{{ $message }}</div>
        @enderror

        <br><br>

        <button type="submit" style="padding: 8px 16px; background-color: #0066cc; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Upload Document
        </button>
    </form>
</div>

<!-- Documents List -->
<div style="border: 1px solid #ddd; padding: 20px; border-radius: 8px;">
    <h3>Uploaded Documents</h3>
    
    @forelse ($documents as $document)
        <div style="border: 1px solid #eee; padding: 15px; margin-bottom: 10px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <strong>{{ $documentTypes[$document->document_type] ?? $document->document_type }}</strong>
                <br>
                <small>{{ $document->file_name }}</small>
                <br>
                <small style="color: #666;">Uploaded: {{ $document->uploaded_at ? $document->uploaded_at->format('d M Y, g:i A') : 'N/A' }}</small>
            </div>
            <div style="display: flex; gap: 10px;">
                @if ($document->file_path)
                    <a href="{{ asset('storage/' . $document->file_path) }}" target="_blank" style="text-decoration: none; color: #0066cc; padding: 5px 10px; border: 1px solid #0066cc; border-radius: 4px;">
                        View
                    </a>
                    <a href="/students/{{ $student->student_id }}/documents/{{ $document->document_id }}/download" style="text-decoration: none; color: #0066cc; padding: 5px 10px; border: 1px solid #0066cc; border-radius: 4px;">
                        Download
                    </a>
                    <button onclick="showReplaceForm({{ $document->document_id }})" style="padding: 5px 10px; background-color: #ff9800; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        Replace
                    </button>
                    <form method="POST" action="/students/{{ $student->student_id }}/documents/{{ $document->document_id }}" onsubmit="return confirm('Are you sure you want to delete this document?');" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" style="padding: 5px 10px; background-color: #f44336; color: white; border: none; border-radius: 4px; cursor: pointer;">
                            Delete
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Replace Form (Hidden by default) -->
        <div id="replace-form-{{ $document->document_id }}" style="display: none; border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 4px; background-color: #f9f9f9;">
            <h4>Replace {{ $documentTypes[$document->document_type] ?? $document->document_type }}</h4>
            <form method="POST"
                action="/students/{{ $student->student_id }}/documents/{{ $document->document_id }}"
                enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <label>New File (JPG, JPEG, PNG, PDF - Max 5MB)</label>
                <br>
                <input type="file"
                    name="document_file"
                    accept=".jpg,.jpeg,.png,.pdf"
                    style="margin-bottom: 10px;">

                @error('document_file')
                    <div style="color: red;">{{ $message }}</div>
                @enderror

                <br><br>

                <button type="submit" style="padding: 8px 16px; background-color: #0066cc; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    Replace Document
                </button>
                <button type="button" onclick="hideReplaceForm({{ $document->document_id }})" style="padding: 8px 16px; background-color: #666; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    Cancel
                </button>
            </form>
        </div>
    @empty
        <p style="color: #666; font-style: italic;">No documents uploaded yet.</p>
    @endforelse
</div>

<script>
function showReplaceForm(documentId) {
    document.getElementById('replace-form-' + documentId).style.display = 'block';
}

function hideReplaceForm(documentId) {
    document.getElementById('replace-form-' + documentId).style.display = 'none';
}
</script>
