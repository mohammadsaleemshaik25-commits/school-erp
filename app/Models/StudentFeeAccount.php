<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentFeeAccount extends Model
{
    public const UPDATED_AT = null;

    protected $primaryKey = 'account_id';

    protected $fillable = [
        'enrollment_id',
        'fee_structure_id',
        'discount_amount',
        'final_tuition_fee',
        'books_status',
        'books_from_school',
        'books_fee_applied',
        'books_fee',
        'net_fee',
        'previous_balance',
        'waived_amount',
        'waived_by',
        'waived_date',
        'total_due',
        'status',
        'closed_at',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'final_tuition_fee' => 'decimal:2',
        'books_fee_applied' => 'decimal:2',
        'books_fee' => 'decimal:2',
        'net_fee' => 'decimal:2',
        'previous_balance' => 'decimal:2',
        'waived_amount' => 'decimal:2',
        'total_due' => 'decimal:2',
        'waived_date' => 'date',
        'closed_at' => 'datetime',
        'books_from_school' => 'boolean',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(
            StudentEnrollment::class,
            'enrollment_id',
            'enrollment_id'
        );
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(
            FeeStructure::class,
            'fee_structure_id',
            'fee_structure_id'
        );
    }

    public function student()
    {
        return $this->hasOneThrough(
            Student::class,
            StudentEnrollment::class,
            'enrollment_id',
            'student_id',
            'enrollment_id',
            'student_id'
        );
    }

    public function academicYear()
    {
        return $this->hasOneThrough(
            AcademicYear::class,
            StudentEnrollment::class,
            'enrollment_id',
            'academic_year_id',
            'enrollment_id',
            'academic_year_id'
        );
    }

    public function classRoom()
    {
        return $this->hasOneThrough(
            ClassRoom::class,
            StudentEnrollment::class,
            'enrollment_id',
            'class_id',
            'enrollment_id',
            'class_id'
        );
    }

    public function section()
    {
        return $this->hasOneThrough(
            Section::class,
            StudentEnrollment::class,
            'enrollment_id',
            'section_id',
            'enrollment_id',
            'section_id'
        );
    }

    public function payments(): HasMany
    {
        return $this->hasMany(
            Payment::class,
            'account_id',
            'account_id'
        );
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(
            StudentFeeAdjustment::class,
            'account_id',
            'account_id'
        );
    }

    // ==========================
    // Books Status Helpers
    // ==========================

    public function booksPurchasedFromSchool(): bool
    {
        return $this->books_status === 'SCHOOL';
    }

    public function booksPurchasedOutside(): bool
    {
        return $this->books_status === 'OUTSIDE';
    }

    public function booksPendingDecision(): bool
    {
        return $this->books_status === 'PENDING';
    }

    public function booksPaid(): bool
    {
        return $this->books_status === 'BOOKS_PAID';
    }

    // ==========================
    // Financial Calculations
    // ==========================

   public function recalculateTotals(): void
{
    // Total due is (Tuition Fee + Books Fee (if School/Pending)) - Discounts/Waivers
    // Wait, the schema has net_fee, final_tuition_fee, books_fee, books_fee_applied.
    
    // We'll use final_tuition_fee + books_fee_applied as the base.
    $tuition = (float) $this->final_tuition_fee;
    $books = (float) $this->books_fee_applied;
    $prevBalance = (float) $this->previous_balance;
    
    $totalBilled = $tuition + $books + $prevBalance;
    
    $discounts = (float) $this->discount_amount + (float) $this->waived_amount;
    
    $this->total_due = max(0, $totalBilled - $discounts);

    $paid = (float) $this->total_paid;

    if ($paid >= $this->total_due) {
        $this->status = 'PAID';
        if (!$this->closed_at) {
            $this->closed_at = now();
        }
    } elseif ($paid > 0) {
        $this->status = 'PARTIALLY_PAID';
        $this->closed_at = null;
    } else {
        $this->status = 'UNPAID';
        $this->closed_at = null;
    }
}

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()
            ->where('status', 'SUCCESS')
            ->sum('amount');
    }

    public function getRemainingBalanceAttribute(): float
    {
        return max(
            0.00,
            (float) ($this->total_due - $this->total_paid)
        );
    }
}