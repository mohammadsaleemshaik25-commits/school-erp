<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentFeeComponentSelection extends Model
{
    public $timestamps = false;

    protected $table = 'student_fee_component_selections';

    protected $fillable = [
        'student_id',
        'enrollment_id',
        'component_id',
        'amount',
        'selected_by',
        'selected_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'selected_at' => 'datetime',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selected_by', 'user_id');
    }
}
