<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeroSetting extends Model
{
    public const TYPE_SLIDER = 'slider';
    public const TYPE_VIDEO = 'video';

    protected $fillable = ['type', 'video_url', 'video_path'];

    public function isVideo(): bool
    {
        return $this->type === self::TYPE_VIDEO && ($this->video_url || $this->video_path);
    }

    public function isSlider(): bool
    {
        return $this->type === self::TYPE_SLIDER;
    }

    /** Video source: full URL (link) or asset URL (uploaded file). */
    public function getVideoSourceUrl(): ?string
    {
        if ($this->video_url) {
            return $this->video_url;
        }
        if ($this->video_path) {
            return asset('storage/'.$this->video_path);
        }
        return null;
    }
}
