<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cars extends Model
{
    protected $fillable = [
        'user_id',
        'license_plate',
        'make',
        'model',
        'price',
        'mileage',
        'seats',
        'doors',
        'production_year',
        'weight',
        'color',
        'image',
        'sold_at',
        'views'
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function carTags()
    {
        return $this->hasMany(CarTags::class, 'car_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tags::class, 'car_tags', 'car_id', 'tag_id');
    }

    public function isOwner(): bool
    {
        return auth()->check() && $this->user_id === auth()->id();
    }
}
