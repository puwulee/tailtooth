<?php

namespace App\Models;

use App\Enums\BracketFormat;
use App\Enums\StageType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stage extends Model
{
    protected $guarded = [];

    protected $casts = [
        'type' => StageType::class,
        'format' => BracketFormat::class,
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function battles(): HasMany
    {
        return $this->hasMany(Battle::class);
    }
}
