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

<div class="text-center mb-3">
    @if($student->photo_path)
        <img src="{{ asset('storage/'.$student->photo_path) }}"
             class="rounded-circle border shadow"
             width="120"
             height="120"
             style="object-fit:cover;">
    @else
        <div style="width: 120px; height: 120px; border-radius: 50%; background-color: #e0e0e0; display: flex; align-items: center; justify-content: center; margin: 0 auto; border: 3px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <span style="font-size: 12px; color: #666;">No Photo</span>
        </div>
    @endif
</div>

<br>

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
