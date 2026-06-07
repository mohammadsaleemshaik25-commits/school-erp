<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentDocument extends Model
{
    protected $table = 'student_documents';

    protected $primaryKey = 'document_id';

    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'document_type',
        'file_name',
        'file_path',
        'uploaded_at',
        'verification_status',
        'verified_at',
        'verified_by',
        'remarks',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    // Status Constants
    public const STATUS_UPLOADED = 'UPLOADED';
    public const STATUS_VERIFIED = 'VERIFIED';
    public const STATUS_REJECTED = 'REJECTED';

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by', 'user_id');
    }
}
