@include('partials.module-nav')

<h1>Student Profile</h1>

<a href="/students">
    Back to Students
</a>

 |

<a href="/students/{{ $student->student_id }}/documents">
    Documents
</a>

 |

<a href="/students/{{ $student->student_id }}/enrollments">
    Enrollments
</a>

 |

<a href="/students/{{ $student->student_id }}/tc">
    Transfer Certificate
</a>

 |

<a href="/students/{{ $student->student_id }}/id-card">
    Generate ID Card
</a>

 |

<a href="/students/{{ $student->student_id }}/history">
    Student History
</a>

 |

<a href="/students/{{ $student->student_id }}/edit">
    Edit Student
</a>

<br><br>

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
        <th>Guardian Name</th>
        <td>{{ $student->guardian_name }}</td>
    </tr>
    <tr>
        <th>Primary Phone</th>
        <td>{{ $student->phone_primary }}</td>
    </tr>
    <tr>
        <th>Secondary Phone</th>
        <td>{{ $student->phone_secondary }}</td>
    </tr>
    <tr>
        <th>Email</th>
        <td>{{ $student->email }}</td>
    </tr>
    <tr>
        <th>Address</th>
        <td>{{ $student->address }}</td>
    </tr>
    <tr>
        <th>Admission Date</th>
        <td>{{ $student->admission_date }}</td>
    </tr>
    <tr>
        <th>Status</th>
        <td>{{ $student->status }}</td>
    </tr>
</table>

<br>

<h2>Enrollment History</h2>

<table border="1" cellpadding="8" cellspacing="0">
    <thead>
        <tr>
            <th>Academic Year</th>
            <th>Class</th>
            <th>Section</th>
            <th>Status</th>
            <th>Promotion Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($enrollmentHistory as $enrollment)
            <tr>
                <td>{{ $enrollment->academicYear?->year_name }}</td>
                <td>{{ $enrollment->classRoom?->class_name }}</td>
                <td>{{ $enrollment->section?->section_name }}</td>
                <td>{{ $enrollment->status }}</td>
                <td>{{ $enrollment->promotion_status }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5">
                    No enrollment history found.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
