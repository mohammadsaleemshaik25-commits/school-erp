<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ReceiptComponentDetail;

class Receipt extends Model
{
    public const UPDATED_AT = null;

    protected $primaryKey = 'receipt_id';

    protected $fillable = [
        'payment_id',
        'receipt_number',
        'receipt_date',
        'generated_datetime',
        'generated_by',
        'status',
        'cancellation_reason',
        'is_duplicate',
        'printed_count',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'generated_datetime' => 'datetime',
        'is_duplicate' => 'boolean',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'payment_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(
            ReceiptComponentDetail::class,
            'receipt_id',
            'receipt_id'
        );
    }
}