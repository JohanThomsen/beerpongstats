<?php

namespace App\Enums;

enum GameUpdateType: string
{
    case START = 'START';
    case END = 'END';
    case MISS = 'MISS';
    case EDGE = 'EDGE';
    case HIT = 'HIT';
    case RERACK = 'RERACK';

    public static function throwResults(): array
    {
        return [
            self::MISS,
            self::EDGE,
            self::HIT,
        ];
    }
}
