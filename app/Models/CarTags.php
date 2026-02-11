<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarTags extends Model
{
    protected $table = 'tags';
    public $timestamps = false;

    protected $fillable = [
        'car_id',
        'tag_id',
    ];

    public function car()
    {
        return $this->belongsTo(Cars::class, 'car_id');
    }

    public function tag()
    {
        return $this->belongsTo(Tags::class, 'tag_id');
    }
}
