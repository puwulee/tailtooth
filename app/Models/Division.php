<?php

namespace App\Models;

use App\Enums\AgeGroup;
use App\Enums\DeckMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Division extends Model
{
    protected $guarded = [];

    protected $casts = [
        'age_group' => AgeGroup::class,
        'deck_mode' => DeckMode::class,
        'fee' => 'decimal:2',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function stages(): HasMany
    {
        return $this->hasMany(Stage::class);
    }

    public function scoreRules(): HasMany
    {
        return $this->hasMany(ScoreRule::class);
    }
}
