<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentComponentAllocation extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'payment_component_allocations';

    protected $fillable = [
        'payment_id',
        'component_account_id',
        'amount_paid',
        'created_at',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'payment_id');
    }

    public function componentAccount(): BelongsTo
    {
        return $this->belongsTo(StudentFeeComponentAccount::class, 'component_account_id', 'id');
    }
}
