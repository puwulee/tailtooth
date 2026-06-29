<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appeal extends Model
{
    protected $guarded = [];

    public function battle(): BelongsTo { return $this->belongsTo(Battle::class); }
    public function player(): BelongsTo { return $this->belongsTo(Player::class); }
}
