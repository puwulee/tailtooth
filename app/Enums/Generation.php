<?php

namespace App\Enums;

enum Generation: string
{
    case BeybladeX = 'beyblade_x';
    case Burst = 'burst';

    public function label(): string
    {
        return match ($this) {
            self::BeybladeX => 'Beyblade X',
            self::Burst => 'Beyblade Burst',
        };
    }
}
