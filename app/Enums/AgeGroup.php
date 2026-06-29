<?php

namespace App\Enums;

enum AgeGroup: string
{
    case Kids = 'kids';
    case Adult = 'adult';
    case Open = 'open';

    public function label(): string
    {
        return match ($this) {
            self::Kids => '兒童組',
            self::Adult => '成人組',
            self::Open => '公開組',
        };
    }
}
