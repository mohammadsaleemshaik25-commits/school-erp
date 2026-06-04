<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card - {{ $student->student_name }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f0f0f0;
            margin: 0;
            padding: 24px;
        }

        .toolbar {
            max-width: 400px;
            margin: 0 auto 16px;
            text-align: center;
        }

        .toolbar a,
        .toolbar button {
            margin: 0 8px;
        }

        .id-card {
            width: 340px;
            margin: 0 auto;
            border: 2px solid #1a365d;
            border-radius: 12px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .id-card-header {
            background: #1a365d;
            color: #fff;
            text-align: center;
            padding: 12px 16px;
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .id-card-body {
            padding: 16px;
            text-align: center;
        }

        .photo-box {
            width: 100px;
            height: 120px;
            margin: 0 auto 16px;
            border: 1px solid #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #f9f9f9;
        }

        .photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-placeholder {
            color: #888;
            font-size: 12px;
        }

        .id-field {
            text-align: left;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .id-field strong {
            display: block;
            color: #555;
            font-size: 11px;
            text-transform: uppercase;
        }

        .id-card-footer {
            border-top: 1px dashed #ccc;
            padding: 12px 16px;
            text-align: right;
            font-size: 12px;
            color: #555;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .toolbar {
                display: none;
            }

            .id-card {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="/students/{{ $student->student_id }}">Back to Profile</a>
        <button type="button" onclick="window.print()">Print / Save PDF</button>
    </div>

    <div class="id-card">
        <div class="id-card-header">
            <img src="{{ asset('build/assets/school/logo.png') }}" alt="School Logo" style="height:40px; margin-bottom:8px;">
            <br>VIKAS SCHOOL
        </div>

        <div class="id-card-body">
            <div class="photo-box">
                @if ($photoDocument?->file_path)
                    <img src="{{ asset('storage/' . $photoDocument->file_path) }}" alt="Student Photo">
                @else
                    <span class="photo-placeholder">PHOTO</span>
                @endif
            </div>

            <div class="id-field">
                <strong>Admission No</strong>
                {{ $student->admission_no }}
            </div>

            <div class="id-field">
                <strong>Student Name</strong>
                {{ $student->student_name }}
            </div>

            <div class="id-field">
                <strong>Class</strong>
                {{ $currentEnrollment?->classRoom?->class_name ?? '—' }}
            </div>

            <div class="id-field">
                <strong>Section</strong>
                {{ $currentEnrollment?->section?->section_name ?? '—' }}
            </div>

            <div class="id-field">
                <strong>Academic Year</strong>
                {{ $currentEnrollment?->academicYear?->year_name ?? '—' }}
            </div>
        </div>

        <div class="id-card-footer">
            Principal Signature
        </div>
    </div>
</body>
</html>
