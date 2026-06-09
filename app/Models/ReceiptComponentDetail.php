<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptComponentDetail extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'receipt_component_details';

    protected $fillable = [
        'receipt_id',
        'component_id',
        'component_name',
        'previous_balance',
        'paid_amount',
        'remaining_balance',
        'created_at',
    ];

    protected $casts = [
        'previous_balance' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class, 'receipt_id', 'receipt_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(FeeComponent::class, 'component_id', 'component_id');
    }
}
