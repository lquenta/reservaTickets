<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeroSlide extends Model
{
    protected $fillable = ['image_path', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];
}
