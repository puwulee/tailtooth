<?php

namespace App\Models;

use App\Enums\Generation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Beyblade extends Model
{
    protected $guarded = [];

    protected $casts = [
        'parts' => 'array',
        'generation' => Generation::class,
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
