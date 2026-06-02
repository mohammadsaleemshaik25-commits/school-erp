<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>VIKAS SCHOOL ERP</title>
</head>
<body>
    @include('partials.module-nav')

    <h1>VIKAS SCHOOL ERP</h1>
    <p>Student &amp; Academic Module</p>

    <h2>Student Management</h2>
    <ul>
        <li><a href="/students">Student List &amp; Search</a></li>
        <li><a href="/students/create">Admission Form (Add Student)</a></li>
        <li><a href="/admissions/register">Admission Register</a></li>
    </ul>

    <h2>Academic</h2>
    <ul>
        <li><a href="/academic-years">Academic Years</a></li>
        <li><a href="/classes">Classes</a></li>
        <li><a href="/sections">Sections</a></li>
        <li><a href="/promotions">Promotion &amp; Passout</a></li>
    </ul>

    <h2>API (for Dashboard / Fee team)</h2>
    <ul>
        <li><a href="/api/dashboard/stats">Dashboard Stats</a></li>
        <li><a href="/api/students">Students API</a></li>
        <li><a href="/api/enrollments">Enrollments API</a></li>
    </ul>
</body>
</html>
