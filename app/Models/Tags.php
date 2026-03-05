<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tags extends Model
{
    protected $table = 'tags';
    public $timestamps = true;
    protected $fillable = [
        'name',
        'color',
    ];
    public function cars()
    {
        return $this->belongsToMany(Cars::class, 'car_tags', 'tag_id', 'car_id');
    }
}
