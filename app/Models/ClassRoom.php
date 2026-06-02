<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassRoom extends Model
{
    protected $table = 'classes';

    protected $primaryKey = 'class_id';

    public $timestamps = false;

    protected $fillable = [
        'class_name',
        'display_order',
    ];

    public function sections()
    {
        return $this->hasMany(Section::class, 'class_id', 'class_id');
    }
}
