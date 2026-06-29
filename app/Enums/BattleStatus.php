<?php

namespace App\Enums;

enum BattleStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Finished = 'finished';
    case Appealed = 'appealed';
    case Confirmed = 'confirmed';
}
