<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassRoom extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'classes';

    protected $primaryKey = 'class_id';

    public $timestamps = true;

    protected $fillable = [
        'class_name',
        'display_order',
    ];

    public function sections()
    {
        return $this->hasMany(Section::class, 'class_id', 'class_id');
    }
}
