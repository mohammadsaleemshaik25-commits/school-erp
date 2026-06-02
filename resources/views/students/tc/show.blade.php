<!DOCTYPE html>
<html>
<head>
    <title>Transfer Certificate {{ $document->file_name }}</title>
</head>
<body>
    <h1>Transfer Certificate</h1>

    <p>
        TC Number: {{ $document->file_name }}
    </p>

    <p>
        Date: {{ $document->uploaded_at?->format('Y-m-d') }}
    </p>

    <hr>

    <table border="1" cellpadding="8" cellspacing="0">
        <tr>
            <th>Admission Number</th>
            <td>{{ $student->admission_no }}</td>
        </tr>
        <tr>
            <th>Student Name</th>
            <td>{{ $student->student_name }}</td>
        </tr>
        <tr>
            <th>Date of Birth</th>
            <td>{{ $student->dob }}</td>
        </tr>
        <tr>
            <th>Gender</th>
            <td>{{ $student->gender }}</td>
        </tr>
        <tr>
            <th>Father Name</th>
            <td>{{ $student->father_name }}</td>
        </tr>
        <tr>
            <th>Mother Name</th>
            <td>{{ $student->mother_name }}</td>
        </tr>
        <tr>
            <th>Admission Date</th>
            <td>{{ $student->admission_date }}</td>
        </tr>
        <tr>
            <th>Last Academic Year</th>
            <td>{{ $latestEnrollment?->academicYear?->year_name }}</td>
        </tr>
        <tr>
            <th>Last Class</th>
            <td>{{ $latestEnrollment?->classRoom?->class_name }}</td>
        </tr>
        <tr>
            <th>Last Section</th>
            <td>{{ $latestEnrollment?->section?->section_name }}</td>
        </tr>
        <tr>
            <th>Status</th>
            <td>{{ $student->status }}</td>
        </tr>
    </table>

    <br><br>

    <p>
        This is to certify that the above student was enrolled in this school and has been marked as transferred.
    </p>

    <br><br>

    <p>
        Principal Signature: ____________________
    </p>

    <script>
        window.print();
    </script>
</body>
</html>
