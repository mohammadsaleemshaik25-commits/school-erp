<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Admission extends Model
{
    protected $primaryKey = 'admission_id';

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'class_id',
        'section_id',
        'admission_status',
        'remarks',
        'transferred_from_admission_id',
        'verified_at',
        'verified_by',
        'approved_by',
        'approved_at',
        'admitted_at',
        'admitted_by',
        'created_by',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'admitted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status Constants
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_SUBMITTED = 'SUBMITTED';
    public const STATUS_VERIFIED = 'DOCUMENT VERIFIED';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_ADMITTED = 'ADMITTED';
    public const STATUS_REJECTED = 'REJECTED';

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    public function transferredFrom(): BelongsTo
    {
        return $this->belongsTo(Admission::class, 'transferred_from_admission_id', 'admission_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by', 'user_id');
    }

    public function admitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admitted_by', 'user_id');
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }
}
