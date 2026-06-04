<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $table = 'students';

    protected $primaryKey = 'student_id';

    protected $fillable = [
        'admission_no',
        'pen_no',
        'aadhaar_no',
        'student_name',
        'dob',
        'gender',
        'father_name',
        'mother_name',
        'guardian_name',
        'phone_primary',
        'phone_secondary',
        'email',
        'address',
        'admission_date',
        'status',
        'photo_path',
    ];

    public function documents()
    {
        return $this->hasMany(StudentDocument::class, 'student_id', 'student_id');
    }

    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class, 'student_id', 'student_id');
    }

    public function currentEnrollment()
    {
        return $this->enrollments()
            ->with(['academicYear', 'classRoom', 'section'])
            ->join('academic_years', 'student_enrollments.academic_year_id', '=', 'academic_years.academic_year_id')
            ->where('student_enrollments.status', 'ACTIVE')
            ->orderByDesc('academic_years.start_date')
            ->select('student_enrollments.*')
            ->first();
    }

    public function latestEnrollment()
    {
        return $this->enrollments()
            ->with(['academicYear', 'classRoom', 'section'])
            ->join('academic_years', 'student_enrollments.academic_year_id', '=', 'academic_years.academic_year_id')
            ->orderByDesc('academic_years.start_date')
            ->select('student_enrollments.*')
            ->first();
    }

    public function photoDocument()
    {
        return $this->documents()
            ->whereRaw('UPPER(document_type) = ?', ['PHOTO'])
            ->orderByDesc('uploaded_at')
            ->first();
    }
}
