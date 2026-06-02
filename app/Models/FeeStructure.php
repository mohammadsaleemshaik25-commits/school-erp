<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeStructure extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'fee_structures';

    protected $primaryKey = 'fee_structure_id';

    protected $fillable = [
        'academic_year_id',
        'class_id',
        'tuition_fee',
        'books_fee',
    ];

    protected function casts(): array
    {
        return [
            'tuition_fee' => 'decimal:2',
            'books_fee' => 'decimal:2',
        ];
    }

    /**
     * Academic Year
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    /**
     * Class
     */
    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    /**
     * Student Fee Accounts
     */
    public function feeAccounts()
    {
        return $this->hasMany(StudentFeeAccount::class, 'fee_structure_id');
    }
}
