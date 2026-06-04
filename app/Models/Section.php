<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'sections';

    protected $primaryKey = 'section_id';

    public $timestamps = true;

    protected $fillable = [
        'class_id',
        'section_name',
    ];

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id', 'class_id');
    }
}
