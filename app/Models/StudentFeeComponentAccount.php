<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentFeeComponentAccount extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'student_fee_component_accounts';

    protected $fillable = [
        'student_id',
        'enrollment_id',
        'component_id',
        'amount',
        'concession_amount',
        'waiver_amount',
        'paid_amount',
        'balance_amount',
        'status',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'concession_amount' => 'decimal:2',
        'waiver_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'created_at' => 'datetime',
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

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentComponentAllocation::class, 'component_account_id', 'id');
    }

    public function recalculateBalance(): void
    {
        $amount = (float) $this->amount;
        $concession = (float) $this->concession_amount;
        $waiver = (float) $this->waiver_amount;
        $paid = (float) $this->paid_amount;

        $balance = $amount - $concession - $waiver - $paid;
        $this->balance_amount = max(0.00, $balance);

        if ($this->balance_amount == 0.00) {
            if ($waiver >= ($amount - $concession - $paid) && $paid == 0) {
                $this->status = 'WAIVED';
            } else {
                $this->status = 'PAID';
            }
        } elseif ($paid > 0) {
            $this->status = 'PARTIALLY_PAID';
        } else {
            $this->status = 'PENDING';
        }
    }
}
