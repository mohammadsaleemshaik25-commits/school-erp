<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentEnrollment extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'student_enrollments';

    protected $primaryKey = 'enrollment_id';

    public $timestamps = true;

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'class_id',
        'section_id',
        'promotion_status',
        'status',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id', 'academic_year_id');
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id', 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id', 'section_id');
    }

    public function feeAccount()
    {
        return $this->hasOne(StudentFeeAccount::class, 'enrollment_id', 'enrollment_id');
    }
}
