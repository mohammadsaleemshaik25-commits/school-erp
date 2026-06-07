<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'academic_years';

    protected $primaryKey = 'academic_year_id';

    public $timestamps = true;

    protected $fillable = [
        'year_name',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];
}
