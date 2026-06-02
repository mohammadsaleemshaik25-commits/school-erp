<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $table = 'sections';

    protected $primaryKey = 'section_id';

    public $timestamps = false;

    protected $fillable = [
        'class_id',
        'section_name',
    ];

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id', 'class_id');
    }
}
