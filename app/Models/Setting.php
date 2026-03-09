<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::getAllCached();
        return $all[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => is_string($value) ? $value : json_encode($value)]
        );
        self::clearCache();
    }

    public static function getMany(array $keys): array
    {
        $all = self::getAllCached();
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = $all[$key] ?? null;
        }
        return $out;
    }

    protected static function getAllCached(): array
    {
        return Cache::remember('settings', 300, function () {
            if (! \Illuminate\Support\Facades\Schema::hasTable('settings')) {
                return [];
            }
            $rows = self::query()->pluck('value', 'key');
            return $rows->toArray();
        });
    }

    public static function clearCache(): void
    {
        Cache::forget('settings');
    }
}
