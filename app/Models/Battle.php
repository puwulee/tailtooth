<?php

namespace App\Models;

use App\Enums\BattleStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Battle extends Model
{
    protected $guarded = [];

    protected $casts = [
        'status' => BattleStatus::class,
        'venue_drawn' => 'boolean',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function playerA(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player_a_id');
    }

    public function playerB(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player_b_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'winner_id');
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(BattleRound::class)->orderBy('sequence');
    }
}
