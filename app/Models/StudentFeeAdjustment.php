<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentFeeAdjustment extends Model
{
    protected $fillable = [
        'student_fee_account_id',
        'adjustment_type', // CONCESSION, WAIVER, PREVIOUS_BALANCE_WAIVER
        'sub_type',        // SIBLING_DISCOUNT, MERIT_SCHOLARSHIP, SPECIAL_CONCESSION, BALANCE_WAIVER
        'amount',
        'reason',
        'requested_by',
        'approved_by',
        'status',          // PENDING, APPROVED, REJECTED
        'decided_at',
        'decision_remarks',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'decided_at' => 'datetime',
    ];

    public function feeAccount(): BelongsTo
    {
        return $this->belongsTo(StudentFeeAccount::class, 'student_fee_account_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}