<?php

namespace App\Enums;

enum BracketFormat: string
{
    case RoundRobin = 'round_robin';
    case Swiss = 'swiss';
    case SingleElim = 'single_elim';
    case DoubleElim = 'double_elim';
}
