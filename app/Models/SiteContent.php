<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteContent extends Model
{
    public const KEY_QUIENES_SOMOS = 'quienes_somos';
    public const KEY_HERO = 'hero';

    protected $fillable = ['key', 'title', 'content'];

    public static function quienesSomos(): ?self
    {
        return static::where('key', self::KEY_QUIENES_SOMOS)->first();
    }

    public static function hero(): ?self
    {
        return static::where('key', self::KEY_HERO)->first();
    }
}
