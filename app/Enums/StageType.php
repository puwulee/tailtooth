<?php

namespace App\Enums;

enum StageType: string
{
    case Group = 'group';
    case Playoff = 'playoff';
    case Final = 'final';
}
