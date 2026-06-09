<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    public const UPDATED_AT = null;

    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'account_id',
        'collected_by',
        'fee_component_type',
        'amount',
        'books_fee_paid',
        'tuition_fee_paid',
        'previous_fee_paid',
        'payment_mode',
        'transaction_reference',
        'payment_date',
        'remarks',
        'status',
        'receipt_generated',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'books_fee_paid' => 'decimal:2',
        'tuition_fee_paid' => 'decimal:2',
        'previous_fee_paid' => 'decimal:2',
        'payment_date' => 'datetime',
        'receipt_generated' => 'boolean',
    ];

    public function feeAccount(): BelongsTo
    {
        return $this->belongsTo(StudentFeeAccount::class, 'account_id', 'account_id');
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by', 'user_id');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by', 'user_id');
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class, 'payment_id', 'payment_id');
    }

    public function allocations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PaymentComponentAllocation::class, 'payment_id', 'payment_id');
    }
}