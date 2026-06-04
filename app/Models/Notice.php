<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    public const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'notice_id';

    protected $fillable = [
        'title',
        'description',
        'image_path',
        'created_by',
        'expiry_date',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }
}
