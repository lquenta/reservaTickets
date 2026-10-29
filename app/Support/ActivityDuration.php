<?php

namespace App\Support;

class ActivityDuration
{
    public static function format(?int $milliseconds): string
    {
        if ($milliseconds === null || $milliseconds <= 0) {
            return '—';
        }

        $seconds = (int) round($milliseconds / 1000);
        if ($seconds < 60) {
            return $seconds.' s';
        }

        $minutes = intdiv($seconds, 60);
        $remainder = $seconds % 60;
        if ($minutes < 60) {
            return $remainder > 0 ? "{$minutes} min {$remainder} s" : "{$minutes} min";
        }

        $hours = intdiv($minutes, 60);
        $minutes = $minutes % 60;

        return $minutes > 0 ? "{$hours} h {$minutes} min" : "{$hours} h";
    }
}
