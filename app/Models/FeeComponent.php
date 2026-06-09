<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeComponent extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'fee_components';
    protected $primaryKey = 'component_id';

    protected $fillable = [
        'component_code',
        'component_name',
        'category',
        'is_optional',
        'status',
        'created_at',
    ];

    protected $casts = [
        'is_optional' => 'boolean',
        'created_at' => 'datetime',
    ];
}
