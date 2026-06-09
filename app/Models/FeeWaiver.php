<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeWaiver extends Model
{
    public $timestamps = false;

    protected $table = 'fee_waivers';
    protected $primaryKey = 'waiver_id';

    protected $fillable = [
        'student_id',
        'enrollment_id',
        'component_id',
        'waiver_amount',
        'reason',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'waiver_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'enrollment_id', 'enrollment_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(FeeComponent::class, 'component_id', 'component_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }
}
