<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarTags extends Model
{
    protected $table = 'car_tags';
    protected $fillable = [
        'car_id',
        'tag_id',
    ];
    public $timestamps = true;
    public function car()
    {
        return $this->belongsTo(Cars::class, 'car_id');
    }
    public function tag()
    {
        return $this->belongsTo(Tags::class, 'tag_id');
    }
}
