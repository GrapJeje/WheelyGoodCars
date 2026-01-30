<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cars extends Model
{
    protected $fillable = [
        'make',
        'model',
        'year',
        'color',
        'price',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function carTags()
    {
        return $this->hasMany(CarTags::class, 'car_id');
    }
}
