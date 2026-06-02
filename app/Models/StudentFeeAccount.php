<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class StudentFeeAccount
 * Represents the primary financial record of a student for a specific academic year.
 */
class StudentFeeAccount extends Model
{
    protected $fillable = [
        'student_id',
        'academic_year_id',
        'class_id',
        'tuition_fee',
        'books_fee_applied',
        'previous_balance_carried',
        'concession_amount',
        'total_due',
        'total_paid',
        'status',
    ];

    protected $casts = [
        'tuition_fee' => 'decimal:2',
        'books_fee_applied' => 'decimal:2',
        'previous_balance_carried' => 'decimal:2',
        'concession_amount' => 'decimal:2',
        'total_due' => 'decimal:2',
        'total_paid' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(StudentFeeAdjustment::class);
    }

    /**
     * Re-calculates totals and determines the payment status.
     * Does not save automatically to allow transaction management in service.
     */
    public function recalculateTotals(): void
    {
        $tuition = (float) $this->tuition_fee;
        $books = (float) $this->books_fee_applied;
        $carried = (float) $this->previous_balance_carried;
        $concessions = (float) $this->concession_amount;

        $this->total_due = ($tuition + $books + $carried) - $concessions;
        $paid = (float) $this->total_paid;

        if ($paid >= $this->total_due) {
            $this->status = 'PAID';
        } elseif ($paid > 0) {
            $this->status = 'PARTIALLY_PAID';
        } else {
            $this->status = 'UNPAID';
        }
    }

    /**
     * Compute remaining outstanding balance
     */
    public function getRemainingBalanceAttribute(): float
    {
        return max(0.00, (float) ($this->total_due - $this->total_paid));
    }
}