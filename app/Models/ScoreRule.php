<?php

namespace App\Models;

use App\Enums\FinishType;
use Illuminate\Database\Eloquent\Model;

class ScoreRule extends Model
{
    protected $guarded = [];

    protected $casts = [
        'finish_type' => FinishType::class,
    ];
}
