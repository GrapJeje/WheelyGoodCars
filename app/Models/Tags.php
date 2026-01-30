<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tags extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function carTags()
    {
        return $this->hasMany(CarTags::class, 'tag_id');
    }
}
