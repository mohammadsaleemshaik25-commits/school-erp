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

    protected $casts = [
    'dob' => 'date',
    'admission_date' => 'date',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
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

    // ==========================
    // Sensitive Data Masking
    // ==========================

    public function getMaskedAadhaarAttribute(): string
    {
        if (empty($this->aadhaar_no)) return '';
        $val = str_replace([' ', '-'], '', $this->aadhaar_no);
        if (strlen($val) <= 4) return $val;
        return str_repeat('*', strlen($val) - 4) . substr($val, -4);
    }

    public function getMaskedPenAttribute(): string
    {
        if (empty($this->pen_no)) return '';
        $val = $this->pen_no;
        if (strlen($val) <= 4) return $val;
        return str_repeat('*', strlen($val) - 4) . substr($val, -4);
    }

    /**
     * Check if current user can view full sensitive data
     */
    public function canViewSensitiveData(): bool
    {
        $role = strtoupper(optional(auth()->user()?->role)->role_name ?? '');
        return in_array($role, ['ADMIN', 'ADMINISTRATOR', 'PRINCIPAL', 'CORRESPONDENT']);
    }
}
