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
        'permanent_address',
        'village',
        'district',
        'state',
        'pin_code',
        'religion',
        'category',
        'blood_group',
        'occupation',
        'annual_income',
        'previous_school',
        'previous_class',
        'admission_date',
        'status',
        'photo_path',
        'admission_type',
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

    /**
     * Masked Aadhaar Number (XXXX XXXX 1234)
     */
    public function getMaskedAadhaarAttribute()
    {
        if (!$this->aadhaar_no) return 'N/A';
        $clean = str_replace([' ', '-'], '', $this->aadhaar_no);
        if (strlen($clean) < 4) return $this->aadhaar_no;
        return 'XXXX XXXX ' . substr($clean, -4);
    }

    /**
     * Masked PEN Number (******7890)
     */
    public function getMaskedPenAttribute()
    {
        if (!$this->pen_no) return 'N/A';
        $clean = trim($this->pen_no);
        if (strlen($clean) < 4) return $this->pen_no;
        return '******' . substr($clean, -4);
    }

    public function canViewSensitiveData(): bool
    {
        $role = strtoupper(optional(auth()->user()?->role)->role_name ?? '');
        return in_array($role, ['ADMIN', 'ADMINISTRATOR', 'PRINCIPAL', 'CORRESPONDENT']);
    }

    public function enrollments(): \Illuminate\Database\Eloquent\Relations\HasMany
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

    // Document Tracker Constants
    public const MANDATORY_DOCS = ['PHOTO', 'STUDENT_AADHAAR'];
    public const OPTIONAL_DOCS = ['BIRTH_CERTIFICATE', 'TC', 'PARENT_AADHAAR'];

    /**
     * Check if the student has all mandatory documents uploaded and not rejected.
     */
    public function hasMandatoryDocuments(): bool
    {
        $docs = $this->documents;

        $hasPhoto = !empty($this->photo_path) || $docs->where('document_type', 'PHOTO')->where('verification_status', '!=', StudentDocument::STATUS_REJECTED)->isNotEmpty();
        if (!$hasPhoto) {
            return false;
        }

        $hasAadhaar = $docs->where('document_type', 'STUDENT_AADHAAR')->where('verification_status', '!=', StudentDocument::STATUS_REJECTED)->isNotEmpty();
        return $hasAadhaar;
    }
}
