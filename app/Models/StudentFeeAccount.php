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
        'books_decision_by',
        'books_decision_date',
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
        'books_decision_date' => 'datetime',
        'closed_at' => 'datetime',
        'books_from_school' => 'boolean',
    ];

    // Books Status Constants
    public const BOOKS_PENDING = 'PENDING';
    public const BOOKS_SCHOOL = 'SCHOOL';
    public const BOOKS_OUTSIDE = 'OUTSIDE';
    public const BOOKS_PAID = 'BOOKS_PAID';

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

    public function decisionMaker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'books_decision_by', 'user_id');
    }

    // ==========================
    // Books Status Helpers
    // ==========================

    public function booksPurchasedFromSchool(): bool
    {
        return $this->books_status === self::BOOKS_SCHOOL;
    }

    public function booksPurchasedOutside(): bool
    {
        return $this->books_status === self::BOOKS_OUTSIDE;
    }

    public function booksPendingDecision(): bool
    {
        return $this->books_status === self::BOOKS_PENDING;
    }

    public function booksPaid(): bool
    {
        return $this->books_status === self::BOOKS_PAID;
    }

    public function hasBooksPayments(): bool
    {
        return $this->payments()
            ->where('status', 'SUCCESS')
            ->where('books_fee_paid', '>', 0)
            ->exists();
    }

    // ==========================
    // Financial Calculations
    // ==========================

    public function recalculateTotals(): void
    {
        // Total due is (Tuition Fee + Books Fee Applied + Previous Balance) - No discounts applied
        $tuition = (float) $this->final_tuition_fee;
        $books = (float) $this->books_fee_applied;
        $prevBalance = (float) $this->previous_balance;

        $totalBilled = $tuition + $books + $prevBalance;

        $this->total_due = max(0, $totalBilled);
        $this->net_fee = $tuition + $books; // Update net_fee as sum of tuition and applied books fee

        $paid = (float) $this->total_paid;

        if ($paid >= $this->total_due && $this->total_due > 0) {
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
        if ($this->status === 'CLOSED' || $this->status === 'PAID') {
            return 0.00;
        }
        return max(
            0.00,
            (float) ($this->total_due - $this->total_paid)
        );
    }

    public function getRemainingBooksBalanceAttribute(): float
    {
        if ($this->status === 'CLOSED' || $this->status === 'PAID') {
            return 0.00;
        }
        $booksPaid = (float) $this->payments()
            ->where('status', 'SUCCESS')
            ->sum('books_fee_paid');

        return max(0.00, (float) $this->books_fee_applied - $booksPaid);
    }

    public function getRemainingTuitionBalanceAttribute(): float
    {
        if ($this->status === 'CLOSED' || $this->status === 'PAID') {
            return 0.00;
        }
        $tuitionPaid = (float) $this->payments()
            ->where('status', 'SUCCESS')
            ->sum('tuition_fee_paid');

        // Note: total_due already subtracts discounts/waivers from (tuition + books + prev)
        // For simplicity in reporting, we treat tuition due as final_tuition_fee + previous_balance - discounts
        $tuitionDue = (float) $this->final_tuition_fee + (float) $this->previous_balance - (float) $this->discount_amount - (float) $this->waived_amount;
        
        return max(0.00, $tuitionDue - $tuitionPaid);
    }

    // ==========================
    // Outstanding Summary Methods
    // ==========================

    public function getOutstandingSummaryAttribute(): array
    {
        $student = $this->student;
        if (!$student) {
            return [];
        }

        // Get all enrollments for this student across academic years
        $allEnrollments = StudentEnrollment::with(['feeAccount', 'academicYear'])
            ->where('student_id', $student->student_id)
            ->where('status', 'ACTIVE')
            ->orderBy('academic_year_id')
            ->get();

        $summary = [];
        $currentYear = AcademicYear::where('is_active', true)->first();

        foreach ($allEnrollments as $enrollment) {
            $feeAccount = $enrollment->feeAccount;
            $yearName = $enrollment->academicYear->year_name;
            $isCurrentYear = $currentYear && $enrollment->academicYear_id === $currentYear->academic_year_id;

            if ($feeAccount) {
                $remainingBalance = (float) $feeAccount->remaining_balance;
                $remainingBooks = (float) $feeAccount->remaining_books_balance;
                $remainingTuition = (float) $feeAccount->remaining_tuition_balance;

                if ($remainingBalance > 0) {
                    if ($isCurrentYear) {
                        $summary['current_year'] = [
                            'year_name' => $yearName,
                            'tuition_outstanding' => $remainingTuition,
                            'books_outstanding' => $remainingBooks,
                            'total_outstanding' => $remainingBalance,
                        ];
                    } else {
                        $summary['previous_years'][] = [
                            'year_name' => $yearName,
                            'total_outstanding' => $remainingBalance,
                        ];
                    }
                }
            }
        }

        return $summary;
    }
}