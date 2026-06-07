<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentFeeAdjustment extends Model
{
    public const UPDATED_AT = null;

    protected $primaryKey = 'adjustment_id';

    protected $fillable = [
        'account_id',
        'adjustment_type',
        'discount_percent',
        'discount_amount',
        'reason',
        'requested_by',
        'approved_by',
        'approval_status',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function feeAccount(): BelongsTo
    {
        return $this->belongsTo(StudentFeeAccount::class, 'account_id', 'account_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by', 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }
}