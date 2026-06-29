<?php

namespace App\Models;

use App\Enums\FinishType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BattleRound extends Model
{
    protected $guarded = [];

    protected $casts = [
        'finish_type' => FinishType::class,
        'is_draw' => 'boolean',
    ];

    public function battle(): BelongsTo
    {
        return $this->belongsTo(Battle::class);
    }

    public function winnerPlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'winner_player_id');
    }

    public function winnerBeyblade(): BelongsTo
    {
        return $this->belongsTo(Beyblade::class, 'winner_beyblade_id');
    }
}
