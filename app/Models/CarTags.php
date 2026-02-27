<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarTags extends Model
{
    protected $fillable = [
        'name',
        'color',
    ];

    public $timestamps = false;

    public function carTags()
    {
        return $this->hasMany(CarTags::class, 'tag_id');
    }
}
